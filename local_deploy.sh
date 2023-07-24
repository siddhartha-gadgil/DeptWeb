#!/bin/bash
set -e
git pull
rm -rf _root-site || true
bundle exec jekyll build --config _config.yml,_config-root.yml --destination ./_root-site
rsync -avz ./_root-site/ /var/www/html/
