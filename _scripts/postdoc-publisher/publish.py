#!/usr/bin/env python3
"""Publish confirmed postdoc profiles from the private getamailid queue."""

import argparse
import fcntl
import json
import os
from pathlib import Path
import re
import shutil
import stat
import subprocess
import sys
import tempfile
from urllib.parse import urlsplit


REQUEST_ID = re.compile(r"^\d{8}-\d{6}-[a-z0-9-]+-[a-f0-9]{12}$")
USER_ID = re.compile(r"^[A-Za-z0-9._-]{1,100}$")


def run(*command, cwd=None):
    env = os.environ.copy()
    env["GIT_TERMINAL_PROMPT"] = "0"
    subprocess.run(command, cwd=cwd, env=env, check=True)


def text_field(record, key, limit, required=True):
    value = record.get(key)
    if not isinstance(value, str) or len(value.encode("utf-8")) > limit or any(ord(c) < 32 for c in value):
        raise ValueError("invalid " + key)
    value = value.strip()
    if required and not value:
        raise ValueError("missing " + key)
    return value


def read_record(path):
    # The queue is owned by the web process; never follow a link as root.
    fd = os.open(path, os.O_RDONLY | os.O_NOFOLLOW)
    try:
        info = os.fstat(fd)
        if not stat.S_ISREG(info.st_mode) or info.st_size > 12000:
            raise ValueError("invalid queue file")
        record = json.loads(os.read(fd, info.st_size + 1))
    finally:
        os.close(fd)
    if not isinstance(record, dict):
        raise ValueError("invalid queue record")
    request_id = path.name[:-len(".ready.json")]
    if record.get("id") != request_id or not REQUEST_ID.fullmatch(request_id):
        raise ValueError("invalid request ID")
    user_id = text_field(record, "user_id", 100)
    if not USER_ID.fullmatch(user_id):
        raise ValueError("invalid IISc user ID")
    for key, limit, required in (
        ("name", 401, True), ("webpage", 500, False), ("phd", 200, True),
        ("position", 200, True), ("research_area", 500, True), ("office", 100, False),
    ):
        text_field(record, key, limit, required)
    url = record["webpage"]
    if url:
        parsed = urlsplit(url)
        if parsed.scheme not in ("http", "https") or not parsed.netloc:
            raise ValueError("invalid webpage")
    return record


def yaml_string(value):
    # JSON strings are valid YAML strings. Always quote form supplied text.
    return json.dumps(value.strip(), ensure_ascii=False)


def yaml_entry(record):
    fields = (
        ("name", "name"), ("webpage", "webpage"), ("phd", "phd"),
        ("user-id", "user_id"), ("position", "position"),
        ("research-area", "research_area"), ("office", "office"),
    )
    lines = ["- name: " + yaml_string(record["name"])]
    lines.extend("  {}: {}".format(key, yaml_string(record[source])) for key, source in fields[1:])
    lines.extend(("  image: true", "  onboarding-id: " + yaml_string(record["id"])))
    return "\n".join(lines) + "\n"


def copy_photo(source, destination):
    fd = os.open(source, os.O_RDONLY | os.O_NOFOLLOW)
    temporary = None
    try:
        info = os.fstat(fd)
        if not stat.S_ISREG(info.st_mode) or not 5 <= info.st_size <= 5 * 1024 * 1024:
            raise ValueError("invalid photograph")
        with os.fdopen(fd, "rb", closefd=False) as src:
            if src.read(3) != b"\xff\xd8\xff":
                raise ValueError("photograph is not JPEG")
            src.seek(0)
            with tempfile.NamedTemporaryFile(dir=destination.parent, prefix=destination.name + ".tmp-", delete=False) as out:
                temporary = Path(out.name)
                shutil.copyfileobj(src, out)
            os.replace(temporary, destination)
            os.chmod(destination, 0o644)
            temporary = None
    finally:
        os.close(fd)
        if temporary is not None and temporary.exists():
            temporary.unlink()


def publish(path, args):
    record = read_record(path)
    repo = args.repo
    request_id = record["id"]
    user_id = record["user_id"]
    photo = args.data_dir / "requests" / (request_id + ".jpg")
    image_path = Path("images") / ("stu-" + user_id + ".jpg")
    image = repo / image_path
    yaml_path = repo / "_data" / "postdocs.yaml"

    if not photo.is_file():
        raise ValueError("missing photograph for " + request_id)
    if not (repo / ".git").exists():
        raise ValueError("publisher checkout is not a git repository")
    clean = subprocess.check_output(("git", "status", "--porcelain"), cwd=repo, universal_newlines=True)
    if clean.strip():
        raise ValueError("publisher checkout has local changes")
    branch = subprocess.check_output(("git", "symbolic-ref", "--short", "HEAD"), cwd=repo, universal_newlines=True).strip()
    if branch != args.branch:
        raise ValueError("publisher checkout must be on " + args.branch)
    run("git", "var", "GIT_AUTHOR_IDENT", cwd=repo)
    run("git", "var", "GIT_COMMITTER_IDENT", cwd=repo)
    run("git", "pull", "--rebase", "origin", args.branch, cwd=repo)

    data = yaml_path.read_text(encoding="utf-8")
    marker = "  onboarding-id: " + yaml_string(request_id)
    if marker not in data:
        existing = re.compile(r"(?im)^\s*user-id:\s*['\"]?" + re.escape(user_id) + r"['\"]?\s*$")
        if existing.search(data) or image.exists():
            raise ValueError("IISc user ID already exists: " + user_id)
        image.parent.mkdir(parents=True, exist_ok=True)
        copy_photo(photo, image)
        with yaml_path.open("a", encoding="utf-8") as out:
            out.write("\n" + yaml_entry(record))
        run("git", "add", "--", str(yaml_path.relative_to(repo)), str(image_path), cwd=repo)
        run("git", "commit", "-m", "Add postdoc profile for " + user_id, cwd=repo)
    elif not image.exists():
        raise ValueError("profile exists without photograph: " + request_id)

    run("git", "push", "origin", args.branch, cwd=repo)
    run("bundle", "exec", "jekyll", "build", "--config", "_config.yml,_config-root.yml",
        "--destination", str(args.build_dir), cwd=repo)
    run("rsync", "-a", str(args.build_dir) + "/", str(args.web_root) + "/")
    path.rename(path.with_name(request_id + ".published.json"))
    print("Published " + request_id + " as " + user_id, flush=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--repo", required=True, type=Path, help="dedicated writable clone of DeptWeb")
    parser.add_argument("--data-dir", type=Path, default=Path("/var/lib/getamailid"))
    parser.add_argument("--web-root", type=Path, default=Path("/var/www/html"))
    parser.add_argument("--build-dir", type=Path, default=Path("/var/lib/getamailid/publish-build"))
    parser.add_argument("--branch", default="master")
    parser.add_argument("--lock", type=Path, default=Path("/run/lock/postdoc-publisher.lock"))
    args = parser.parse_args()
    args.repo = args.repo.resolve()
    args.data_dir = args.data_dir.resolve()
    args.web_root = args.web_root.resolve()
    args.build_dir = args.build_dir.resolve()
    args.lock.parent.mkdir(parents=True, exist_ok=True)
    with args.lock.open("a+") as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        failed = False
        for path in sorted((args.data_dir / "requests").glob("*.ready.json")):
            try:
                publish(path, args)
            except (OSError, ValueError, subprocess.CalledProcessError, json.JSONDecodeError) as exc:
                print("Could not publish {}: {}".format(path.name, exc), file=sys.stderr, flush=True)
                failed = True
        return 1 if failed else 0


if __name__ == "__main__":
    sys.exit(main())
