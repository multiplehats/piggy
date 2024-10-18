# Leat WordPress Plugin

## Getting started

Run the following commands to get started:

```bash
pnpm install
```

## Plugin Development Environments

The plugin makes use of [the `@wordpress/env` package](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).
This supplies convenient commands for creating, destroying, cleaning, and testing WordPress environments.

```bash
# Make sure you are in the working directory of the plugin you are interested in setting up the environment for
cd apps/plugin
# Start will create the environment if necessary or start an existing one
pnpm run env:start
# Stop will, well, stop the environment
pnpm run env:stop
# Destroy will remove all of the environment's files.
pnpm run env:destroy
```

### Users

The default user for the environment is `admin` with the password `password`. You can also login as a customer with the username `customer` and the password `password`.

## Troubleshooting

If you are having issues with scripts not running, you may need to make them executable.

```bash
chmod +x ./bin/*
```
