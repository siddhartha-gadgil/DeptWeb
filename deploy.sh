#!/bin/bash
set -e
git pull
host=${1:-"root@math.iisc.ac.in"}
target=${2:-~/var/www/html}
echo $host
bundle exec jekyll build --config _config.yml,_config-root.yml --destination ./_root-site
rsync -avz ./_root-site/ $host:$target/
