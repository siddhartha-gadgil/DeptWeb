#!/bin/bash

# Check if git pull returns "Already up to date"
if [[ $(git pull) == "Already up to date." ]]; then
  echo "No update to repository, exiting"
else
  echo "Update to repository, running deploy script"
  local_deploy.sh
fi

