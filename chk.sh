#!/bin/bash

# Check if git pull returns "Already up to date"
if [[ $(git pull) == "Already up to date." ]]; then
  echo "Repo is up to date, skipping script"
else
  echo "Repo is not up to date, running script"
  # Run your script here
fi

