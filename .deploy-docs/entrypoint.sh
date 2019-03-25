#!/bin/sh -l
apt-get -y -qq update
apt-get -y -qq upgrade
apt-get -y -qq install curl software-properties-common gnupg apt-transport-https
curl -sL https://deb.nodesource.com/setup_11.x | bash -
apt-get -y -qq install nodejs
curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
apt-get -y -qq update
apt-get -y -qq remove cmdtest
apt-get -y -qq install yarn
yarn install