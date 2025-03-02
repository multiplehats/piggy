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
