#!/bin/bash
set -e
git pull
host=${1:-"root@math.iisc.ac.in"}
target=${2:-/var/www/html}
repo_root="$(pwd)"

# Build only from the committed tree (HEAD) in a throwaway clean checkout --
# never straight from this working directory. Building from the working
# directory meant any local-only scratch file (a mid-edit test, or a
# permanently untracked folder like temp/ or vendor/) could ride along into
# production the moment this ran, committed or not. Archiving HEAD guarantees
# only what's actually committed and pushed ever gets built or deployed.
clean_src="$(mktemp -d)"
trap 'rm -rf "$clean_src"' EXIT
git archive HEAD | tar -x -C "$clean_src"

rm -rf _root-site || true
# If Gemfile.lock changed (e.g. a Dependabot bump someone merged) and this
# machine is missing the pinned versions, install them before building
# instead of letting `bundle exec` crash the whole deploy.
(cd "$clean_src" && (bundle check || bundle install) && bundle exec jekyll build --config _config.yml,_config-root.yml --destination "$repo_root/_root-site")

rsync -avz ./_root-site/ $host:$target/
