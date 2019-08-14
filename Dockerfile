FROM alpine:3.6

WORKDIR /app

COPY . /app 


RUN apk add --update ruby \
    && apk add zlib \
    && apk add --virtual build-dependencies build-base ruby-dev libffi-dev \
    && gem install bundler --no-ri --no-rdoc \
    && bundle install --without development test \
    && gem cleanup \
    && apk del build-dependencies

RUN git pull 

CMD ["bundle", "exec", "jekyll", "build", "--config", "_config.yml,_config-root.yml", "--destination", "/var/_site"]