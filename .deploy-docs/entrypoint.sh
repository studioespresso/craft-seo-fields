#!/bin/sh -l

sh -c "echo $*"
env
cd docs
pwd

apt-get install curl
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
apt-get install yarn
yarn install