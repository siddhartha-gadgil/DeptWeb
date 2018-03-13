#!/bin/bash
set -e
cd /home/gadgil/code/DeptWeb
git pull
/usr/local/bin/bundle exec jekyll build --config _config.yml,_config-root.yml --destination ./_root-site
mkdir -p tarball
tar -czf tarball/dept.tgz _root-site
