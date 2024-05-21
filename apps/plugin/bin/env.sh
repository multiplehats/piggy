#!/bin/bash

########################################################################
# Filename   : env.sh
# Author     : Chris Jayden
# Purpose    : Set up environment variables for the plugin build scripts.
########################################################################

# Current directory
CURR_DIR=$(pwd)

## Plugin path
PLUGIN_PATH=$(dirname "$CURR_DIR")

## Dist folder.
PLUGIN_DIST_PATH=$PLUGIN_PATH/dist

## Monorepo folder
ROOT_PATH=$PLUGIN_PATH/../..

## Packages folder
PACKAGES_PATH=$ROOT_PATH/packages

## Apps folder
APPS_PATH=$ROOT_PATH/apps

# Tool for grabbing version from package.json
get_version() {
	grep '\"version\"' $PLUGIN_PATH/package.json \
	| cut -d ':' -f 2 \
	| sed 's/"//g' \
	| sed 's/,//g' \
	| sed 's/ //g'
}

# Set version
VERSION=$(get_version)