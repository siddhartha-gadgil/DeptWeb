FROM ubuntu:18.04

WORKDIR /app

COPY . /app 


RUN sudo apt-get install build-essential patch ruby-dev zlib1g-dev liblzma-dev  \
    && gem install bundler --no-ri --no-rdoc \
    && bundle install --without development test \
    && gem cleanup 


RUN git pull 

CMD ["bundle", "exec", "jekyll", "build", "--config", "_config.yml,_config-root.yml", "--destination", "/var/_site"]