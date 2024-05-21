#!/usr/bin/env bash

########################################################################
# Filename   : dev-env-setup.sh
# Author     : Chris Jayden
# Purpose    : Runs the setup for the dev environment against the Docker container.
#              See @wordpress/env NPM package for more info.
########################################################################

ENABLE_TRACKING="${ENABLE_TRACKING:-0}"

echo -e 'Activate twentytwentyfour theme \n'
wp-env run cli wp theme activate twentytwentyfour

echo -e 'Update URL structure \n'
wp-env run cli wp rewrite structure '/%postname%/' --hard

echo -e 'Add Customer user \n'
wp-env run cli wp user create customer customer@piggy.dev \
    --user_pass=password \
    --role=subscriber \
    --first_name='Jane' \
    --last_name='Smith'

echo -e 'Add WordPress Admin user \n'
wp-env run cli wp user create admin admin@piggy.dev \
    --user_pass=password \
    --role=administrator \
    --first_name='John' \
    --last_name='Doe'

echo -e 'Update Blog Name \n'
wp-env run cli wp option update blogname 'Piggy: Dev'

echo -e 'Update Permalink structure \n'
wp-env run cli wp rewrite flush

echo -e 'Install WooCommerce \n'
wp-env run cli wp plugin install woocommerce --activate

echo -e 'Importing WooCommerce Sample Data \n'
wp-env run cli wp plugin install wordpress-importer --activate
wp-env run cli wp import ./wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip --quiet