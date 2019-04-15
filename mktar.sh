#!/bin/bash
set -e
cd /home/gadgil/code/DeptWeb
git pull
bundle exec jekyll build --incremental --config _config.yml,_config-root.yml --destination ./_root-site
mkdir -p tarball
tar -czf tarball/dept.tgz _root-site
