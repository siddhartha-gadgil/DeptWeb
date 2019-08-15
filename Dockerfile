FROM ubuntu:18.04

WORKDIR /app

COPY . /app 

RUN rm -rf sources out purged tarball _site _root-site

RUN apt-get update  \
    && apt-get install --assume-yes ruby-full build-essential zlib1g-dev liblzma-dev git < /dev/null  \
    && gem install -v "1.16.4" bundler --no-ri --no-rdoc \
    && bundle update github-pages \
    && bundle install --without development test \
    && gem cleanup 


RUN git pull 

CMD ["bash", "cmd.sh"]