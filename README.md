# Leat WordPress Integration

This repository contains the monorepo for the Leat WordPress/WooCommerce integration plugin and its related components. This plugin serves as a middleware layer connecting WordPress and WooCommerce stores to the Leat CRM platform.

## Project Overview

Leat is a standalone software solution for customer loyalty and email marketing. This integration plugin enables WordPress and WooCommerce users to connect their stores to the Leat platform, allowing them to leverage Leat's loyalty program, rewards, and marketing automation features both online and in-store.

The project uses a monorepo structure with Turborepo to manage the various components:

- **WordPress Plugin**: The main integration plugin for WordPress
- **Admin Interface**: Svelte-based admin dashboard for configuration
- **Frontend Components**: Customer-facing components for the loyalty program
- **WooCommerce Blocks**: Custom blocks for WooCommerce integration
- **Shared Libraries**: Common utilities and types used across the project

## Technology Stack

- **Backend**: PHP 8.0+
- **Frontend**: TypeScript, Svelte, React (WooCommerce Blocks)
- **Build Tools**: Vite, Turborepo, PNPM
- **Testing**: No tests yet.
- **Styling**: The admin interface uses TailwindCSS, the frontend uses vanilla CSS.

## Development Setup

To set up the development environment, follow these steps:

### Prerequisites

- Node.js 22.x or higher
- PNPM 9.x or higher
- PHP 8.0 or higher (64-bit)
- WordPress 6.0+ development environment
- WooCommerce 6.9+
- Leat account (for API access)

### Installation

1. Clone this repository
2. Install dependencies:
    ```bash
    pnpm install
    ```

### Make Scripts Executable

In the `apps/plugin` folder, you may need to ensure that the scripts are executable. Run the following command:

```bash
chmod +x ./bin/*
```

These scripts are used to build the plugin.

### Symlink the Plugin

It's often easier to symlink the plugin into your WordPress environment rather than clone the entire repository inside the `plugins` folder.

To create a symbolic link to your plugin in a WordPress environment, do the following:

1. Navigate to your WordPress plugin folder. For example:

```bash
cd /path/to/wordpress/wp-content/plugins
```

2. Run the following command to create the symlink:

```bash
ln -s /path/to/leat/apps/plugin leat
```

This will create a symlink called `leat` in your WordPress plugins directory, pointing to the Leat plugin within your development environment.

## Development Workflow

The project uses Turborepo to manage the build process and dependencies between packages. Here are some common commands:

```bash
# Start development server for all packages
pnpm dev

# Start development server for admin interface only
pnpm dev:admin

# Start development server for frontend components only
pnpm dev:frontend

# Build all packages
pnpm build

# Package the plugin for distribution
pnpm package-plugin

# Run type checking
pnpm typecheck

# Format code
pnpm format

# Lint code
pnpm lint
```

## Project Structure

- `apps/plugin/` - The main WordPress integration plugin
- `packages/plugin-admin/` - Admin interface components
- `packages/plugin-frontend/` - Frontend components for customers
- `packages/wc-blocks/` - WooCommerce block integrations
- `packages/types/` - Shared TypeScript types
- `packages/lib/` - Shared utilities and libraries

## Integration Features

This plugin connects WordPress/WooCommerce to the Leat platform, enabling:

- **Customer Synchronization**: Automatically sync WordPress users with Leat CRM
- **Order Tracking**: Send order data to Leat for points calculation and reward triggers
- **Reward Redemption**: Allow customers to redeem Leat rewards in your WooCommerce store
- **Loyalty Widget Integration**: Display loyalty program information to customers
- **Admin Configuration**: Manage Leat connection settings from WordPress admin
- **WooCommerce Checkout Integration**: Apply rewards and collect loyalty information during checkout

## External Services

This plugin requires an API connection to [Leat](https://www.leat.com) and its business portal ([business.leat.eu](https://business.leat.com)) for core functionality. The plugin acts as a middleware layer, transmitting data between your WordPress site and the Leat platform.

**Data transmitted includes**:

- Customer information (email, name, order history, purchase categories)
- WooCommerce order data (order totals, products purchased, dates)
- Store configuration data
- Reward and voucher definitions
- Store performance metrics and analytics

All data is transmitted securely via HTTPS to Leat's servers in compliance with GDPR and other applicable privacy regulations.

## Contributing

We welcome contributions! Please read our [Contributing Guidelines](CONTRIBUTING.md) before submitting pull requests.

## License

This integration plugin is licensed under the GPL v2 or later - see the [LICENSE](/apps/plugin/license.txt) file for details.

## Release Process

To create a new release of the Leat WordPress Integration plugin, follow these steps:

### 1. Update Changelog

Add your changelog entries to `apps/plugin/readme.txt` in the Changelog section. Follow the existing format:

```
= x.x.x =

* Feature: Description of new feature
* Enhancement: Description of enhancement
* Bug fix: Description of bug fix
```

### 2. Bump Version Numbers

Update the version number in the following files:

- `apps/plugin/package.json`: Update the `"version"` field
- `apps/plugin/leat-crm.php`: Update both the `Version:` and the `LEAT_VERSION` constant
- `apps/plugin/readme.txt`: Update the `Stable tag:` field in the readme header

### 3. Generate Translation Files

Generate the POT file for translations:

```bash
cd apps/plugin
pnpm run i18n
```

This will create/update the `languages/leat.pot` file with the latest translatable strings.

### 4. Test the Build

Build and package the plugin to ensure everything works correctly:

```bash
pnpm build
pnpm package-plugin
```

### 5. Commit Changes

Commit all your changes with a descriptive message:

```bash
git add .
git commit -m "chore: bump version to x.x.x"
```

### 6. Create GitHub Release

1. Create a new tag matching your version number:

    ```bash
    git tag vx.x.x
    git push origin vx.x.x
    ```

2. Go to GitHub and create a new release using the tag you just pushed.
    - Title: Version x.x.x
    - Description: Copy the changelog entries for this version

### 7. Deployment

The GitHub workflow will automatically deploy the plugin to the WordPress.org plugin repository when you publish the release. The workflow:

1. Builds the plugin
2. Packages it for distribution
3. Deploys it to WordPress.org
4. Attaches the zip file to the GitHub release

### Troubleshooting

If the deployment fails, check:

- GitHub Actions logs for any errors
- Ensure the SVN_USERNAME and SVN_PASSWORD secrets are correctly set in the repository
- Verify that the version numbers are consistent across all files
