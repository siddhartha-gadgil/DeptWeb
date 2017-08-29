git pull
target=${2:-~/public}
bundle exec jekyll build --config _config.yml,_config-root.yml --destination ./_root-site
rsync -avz ./_root-site/ $1:$target/
