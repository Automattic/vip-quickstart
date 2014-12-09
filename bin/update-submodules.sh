#!/bin/bash

# Backup untracked www
if [ ! -e /srv/www/.git ]; then
    mv /srv/www /srv/www.bak
fi

# Get submodules
cd /srv && git submodule update --init --recursive

# Track the master branch in www
cd /srv/www && git pull origin master

# Clean up
if [ -d /srv/www.bak ]; then
    rsync -abviuzP /srv/www.bak/ /srv/www/
    rm -rf /srv/www.bak
fi
