#!/usr/bin/env bash

# Exit if any command fails
set -eo pipefail

RELEASE_VERSION=$1

if [ -z !$RELEASE_VERSION ]; then
    $RELEASE_VERSION=$(git describe --tags --abbrev=0)
fi

FILENAME_PREFIX="Plugin_PrestaShop_1_6_$RELEASE_VERSION"
RELEASE_FOLDER=".dist"

# Remove old folder
rm -rf "$RELEASE_FOLDER"

mkdir "$RELEASE_FOLDER"

for dir in "multisafepay"*;
do
  git archive --format=zip -9 --output="$RELEASE_FOLDER/$FILENAME_PREFIX-$dir.zip" HEAD
done

git archive --format=zip -9 --output="$RELEASE_FOLDER/$FILENAME_PREFIX.zip" HEAD
