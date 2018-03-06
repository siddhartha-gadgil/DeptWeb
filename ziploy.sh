#!/bin/bash
set -e
target=${1:-~/var/www/html}
curl -O http://10.134.13.102:6060/dept.tgz
tar -xzf dept.tgz
mkdir -p $target
cp -r _root-site/* $target
