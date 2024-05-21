# PIGGY Monorepo

### Development

#### Scripts

May have to run `chmod +x ./bin/*` in the plugisn folder to make the scripts executable.

### Symlink the plugin

`cd` into your WP plugin folder, e.g. `/Users/jayden/Code/local-by-flywheel/mysite/app/public/wp-content/plugins`

And run the following command:

```
ln -s /Users/jayden/Code/piggy/plugins/experiment/piggy-turborepo/apps/plugin piggy-exp
```

## Research

### Vite manifest

- https://github.com/swashata/wpackio-enqueue/blob/master/inc/Enqueue.php
- https://wpack.io/guides/using-wpackio-enqueue/

- https://github.com/owlsdepartment/vite-plugin-dev-manifest

### Performing SQL migrations

- https://github.com/myparcelnl/woocommerce/blob/main/woocommerce-myparcel.php

```php
    $all_options = wp_load_alloptions();
    $saved_array = [];

    foreach ( $all_options as $name => $value ) {
        // if $name contains piggy_ migrate it to one entry json
        if ( strpos( $name, 'piggy_' ) !== false ) {
            $name = str_replace( 'piggy_', '', $name );

            $saved_array[$name] = $value;
            // add_option( 'piggy', $saved_array );
        }
    }

    error_log(print_r($saved_array, true));
    $delete = delete_option('piggy');
    $saved = update_option( 'piggy', $saved_array );

    error_log(print_r($saved, true));
```

### Advanced state management

- https://github.com/spierala/mini-rx-svelte-demo/blob/78cd7b262e0afcf6b2d71ae4bc043178c0ec96ed/frontend/src/modules/products/services/product-api.service.ts

## Breaking cahnges

### CSS Variable name change

From

```css
.foo {
	color: var(--color-piggy-text-primary, #000);
}
```

To:

```css
color: var(--piggy-color-typography-primary, #000);
```

### Most hooks are gone

### Product Recommendations Engine has been deprecated in favor of Cart Cross-sells

This will align better with WooCommerce. Will have more intuivitive upsell ways later.
