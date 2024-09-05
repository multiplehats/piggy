# Piggy Monorepo

This repository contains the monorepo for the Piggy WordPess plugin and its related components.

## Development Setup

To set up the development environment, follow these steps:

### Make Scripts Executable

In the `plugins` folder, you may need to ensure that the scripts are executable. Run the following command:

```bash
chmod +x ./bin/*
```

These scripts are used to build the plugin.

### Symlink the Plugin

To create a symbolic link to your plugin in a WordPress environment, do the following:

1. Navigate to your WordPress plugin folder. For example:

```bash
cd /Users/jayden/Code/local-by-flywheel/mysite/app/public/wp-content/plugins
```

2. Run the following command to create the symlink:

```bash
ln -s /Users/jayden/Code/piggy/plugins/piggy/apps/plugin piggy
```

This will create a symlink called `piggy` in your WordPress plugins directory, pointing to the Piggy plugin within your development environment.
