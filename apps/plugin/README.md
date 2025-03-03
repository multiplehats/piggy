# Leat WordPress Plugin

## Getting started

Run the following commands to get started:

```bash
pnpm install
```

## Architecture Overview

This plugin implements a pragmatic, simplified clean architecture pattern tailored for WordPress plugin development:

### Domain Layer

- **Interfaces**: Define contracts that infrastructure components must implement
    - `WPPromotionRuleRepositoryInterface`: Interface for promotion rule repository operations
    - `GiftcardProductServiceInterface`: Interface for gift card product business logic
    - `GiftcardRepositoryInterface`: Interface for gift card data access operations
- **Services**: Contain business logic independent of WordPress implementation details
    - `PromotionRulesService`: Manages promotion rules business logic
    - `GiftcardProductService`: Manages gift card product business logic

### Infrastructure Layer

- **Repositories**: Implement domain interfaces with WordPress-specific code
    - `WPPromotionRuleRepository`: WordPress implementation of the promotion rule repository
    - `WPGiftcardRepository`: WordPress implementation of the gift card repository
- **Constants**: Define WordPress-specific constants
    - `WPPromotionRuleMetaKeys`: Constants for WordPress meta keys
- **Formatters**: Transform WordPress data structures
    - `WPPromotionRuleFormatter`: Formats WordPress posts into domain objects
- **UI**: Handle WordPress-specific UI operations
    - `WPGiftcardProductUI`: WordPress-specific UI operations for gift card products

### Bootstrap

- `Bootstrap`: Initializes and wires together the components
    - Each component is responsible for registering its own WordPress hooks
    - Services and UI components have an `init()` method that registers their hooks

**Note**: This is an adapted clean architecture approach that focuses on practical separation of concerns within the WordPress ecosystem. We've implemented key concepts like dependency inversion and separation of business logic from infrastructure, while omitting some of the more complex layers and patterns found in full clean architecture implementations.

This simplified approach provides:

- Reasonable separation between WordPress-specific code and business logic
- Improved testability through interfaces and dependency injection
- Maintainable codebase that still feels familiar to WordPress developers
- Consistent pattern where each component registers its own hooks

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
