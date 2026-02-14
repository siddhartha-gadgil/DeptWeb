import sys
import os
from pathlib import Path
import requests
from bs4 import BeautifulSoup
from urllib.parse import unquote, urlparse, urljoin
import logging

# Configure logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# Caching
memo = {}
# Some problematic links that we want to skip checking are preset as fine.
mcheck = {"https://www.ganeshvaidya.in/": False, "http://ganeshvaidya.in": False, 'https://www.ugc.gov.in/Fellowship/stu_Fellowship2': False}

def trm(s: str) -> str:
    """Normalize local paths."""
    if s.startswith("/DeptWeb/"):
        return s[9:]
    if s.startswith("/"):
        return s[1:]
    return s

def get_document(path_or_url: str, base_dir: Path):
    """Fetch and parse an HTML document, from a URL or a local file."""
    if path_or_url in memo:
        return memo[path_or_url]

    try:
        if path_or_url.startswith("http"):
            response = requests.get(path_or_url, verify=False)
            response.raise_for_status()
            doc = BeautifulSoup(response.text, 'html.parser')
        else:
            # It's a local file
            file_path = base_dir / trm(path_or_url)
            with open(file_path, 'r', encoding='utf-8') as f:
                doc = BeautifulSoup(f, 'html.parser')

        memo[path_or_url] = doc
        return doc
    except Exception as e:
        logging.error(f"Failed to get document for {path_or_url}: {e}")
        return None

def get_sublinks(path_or_url: str, base_dir: Path):
    """Extract all hrefs from a document."""
    doc = get_document(path_or_url, base_dir)
    if not doc:
        return []

    links = []
    for link in doc.select("a"):
        href = link.get("href")
        if href:
            # Clean up the link
            cleaned_href = href.strip().replace("\n", " ").replace(" ", "%20")
            if '#' in cleaned_href:
                cleaned_href = cleaned_href.split("#")[0]
            if cleaned_href:
                links.append(cleaned_href)
    return links

def is_broken(link: str, source_path: str, base_dir: Path, all_files: set):
    """Check if a link is broken."""
    if link in mcheck:
        return mcheck[link]

    # Skip special links
    if link.startswith("mailto:") or \
       link.startswith("https://outlook.office.com/calendar") or \
       link.startswith("https://calendar.google.com/calendar") or \
       link == "http://acadserver.admin.iisc.ac.in/course/":
        mcheck[link] = False
        return False

    # Check external links
    if link.startswith("http"):
        try:
            # Emulate `Try{...}.getOrElse(false)` from scala script
            response = requests.head(link, allow_redirects=True, timeout=60, verify=False)
            is_missing = response.status_code == 404
        except requests.RequestException as e:
            logging.warning(f"Could not check external link {link}: {e}")
            is_missing = False # Treat as not broken if request fails
        mcheck[link] = is_missing
        return is_missing

    # Check local links
    # This part replicates the strange `s+"/../"+l` logic
    # It seems to be trying to resolve relative paths

    # First, try resolving relative to the source page directory
    source_dir = Path(source_path).parent
    try_path1_str = str(source_dir / link)
    try_path1 = base_dir / trm(unquote(try_path1_str))

    # Second, try resolving relative to the base directory
    try_path2 = base_dir / trm(unquote(link))

    is_missing = not (try_path1.exists() or try_path2.exists())

    if is_missing:
         # A special case from the original script for paths that might be relative to a parent
        try_path3_str = str(Path(source_path).parent / ".." / link)
        try_path3 = (base_dir / trm(unquote(try_path3_str))).resolve()
        is_missing = not try_path3.exists()


    mcheck[link] = is_missing
    return is_missing

def find_all_local_pages(base_dir: Path):
    """Crawl the site to find all local HTML pages, starting from index.html."""

    all_files = {p for p in base_dir.rglob("*") if p.is_file()}

    # Convert all_files to relative paths for matching
    relative_all_files = {p.relative_to(base_dir) for p in all_files}


    q = {"index.html"}
    visited = set()

    while q:
        current_page_path = q.pop()
        if current_page_path in visited:
            continue

        # Exclusions from the original script
        if "pubs.html" in current_page_path or "fpsac" in current_page_path:
            continue

        visited.add(current_page_path)

        doc = get_document(current_page_path, base_dir)
        if not doc:
            continue

        for link in get_sublinks(current_page_path, base_dir):
            if not link.startswith("http") and not link.startswith("#") and link.endswith(".html"):

                # Normalize the link path for checking
                normalized_link = trm(unquote(link))

                # Resolve the link relative to the current page
                # current_page_path is relative to base_dir, so join them first
                absolute_current_path = base_dir.resolve() / current_page_path
                resolved_link = (absolute_current_path.parent / normalized_link).resolve()

                try:
                    relative_link = resolved_link.relative_to(base_dir.resolve())
                    if relative_link in relative_all_files and str(relative_link) not in visited:
                        q.add(str(relative_link))
                except ValueError:
                    # This can happen if the link resolves to outside the base_dir, which is fine
                    pass

    return visited, all_files

def main():
    site_dir = Path("_site")
    if not site_dir.is_dir():
        logging.error(f"'{site_dir}' directory not found. Build the Jekyll site first.")
        sys.exit(1)

    logging.info("Starting link check...")

    local_pages, all_files = find_all_local_pages(site_dir)

    logging.info(f"Found {len(local_pages)} local pages to check.")

    broken_links_found = False
    all_broken_links = []
    for i, page_path in enumerate(local_pages):
        logging.info(f"Checking page ({i+1}/{len(local_pages)}): {page_path}")
        if i % 50 == 0:
            logging.info(f"Checked links from {i} pages")

        sublinks = get_sublinks(page_path, site_dir)

        if len(sublinks) >= 500:
            logging.warning(f"Skipping page with too many links ({len(sublinks)}): {page_path}")
            continue

        page_broken_links = []
        for link in sublinks:
            if is_broken(link, page_path, site_dir, all_files):
                page_broken_links.append(link)

        if page_broken_links:
            broken_links_found = True
            all_broken_links.extend(page_broken_links)
            print(f"\n* Broken links in: {page_path}", file=sys.stderr)
            for broken_link in page_broken_links:
                print(f"  * {broken_link}", file=sys.stderr)

    if broken_links_found:
        logging.error(f"Broken links found: {set(all_broken_links)}")
        sys.exit(1)
    else:
        logging.info("No broken links found. Success!")

if __name__ == "__main__":
    main()
