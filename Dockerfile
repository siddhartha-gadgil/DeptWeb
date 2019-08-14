FROM ubuntu:18.04

WORKDIR /app

COPY . /app 


RUN apt-get update  \
    && apt-get install --assume-yes ruby-full build-essential zlib1g-dev liblzma-dev < /dev/null  \
    && gem install -v "1.16.4" bundler --no-ri --no-rdoc \
    && bundle install --without development test \
    && gem cleanup 


RUN git pull 

CMD ["bundle", "exec", "jekyll", "build", "--config", "_config.yml,_config-root.yml", "--destination", "/var/_site"]