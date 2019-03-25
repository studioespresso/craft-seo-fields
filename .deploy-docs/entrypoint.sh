#!/bin/sh -l

sh -c "echo $*"
echo GITHUB_EVENT_NAME
echo GITHUB_REF
cd docs
pwd