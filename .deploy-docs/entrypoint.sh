#!/bin/sh -l

sh -c "echo $*"
env
cd docs
pwd
yarn install