# Gift Card Balance Checker for WooCommerce Blocks

This component enhances the WooCommerce checkout by adding gift card balance checking functionality to the coupon input field and to applied coupons in the cart and checkout blocks.

## Features

- Displays the gift card balance when entering a gift card code in the coupon field
- Shows the balance of applied gift card coupons in the cart and checkout
- Integrates seamlessly with WooCommerce Blocks
- Uses React components for a modern, responsive user experience

## Implementation Details

The gift card balance checker consists of two main components:

1. **React Components (`GiftCardBalanceChecker.tsx`)**:

    - `GiftCardBalanceChecker`: Displays the balance for a given coupon code
    - `GiftCardCouponInput`: A complete coupon input field with balance checking
    - `initGiftCardIntegration`: The main function that integrates with WooCommerce Blocks

2. **Integration Module (`giftcard-checkout-integration.ts`)**:
    - Entry point that initializes the gift card integration with WooCommerce Blocks

## Building the Components

To build the React components, you need to:

1. Install the dependencies:

    ```bash
    npm install
    ```

2. Build the components:

    ```bash
    npm run build:react
    ```

3. For development with hot-reloading:
    ```bash
    npm run dev:react
    ```

## Integration with WooCommerce Blocks

The integration with WooCommerce Blocks is achieved through:

1. **PHP Integration Class**: `GiftcardCouponIntegration` implements the WooCommerce Blocks `IntegrationInterface`
2. **WooCommerce Blocks Checkout Filter Registry**: Using `registerCheckoutFilters` to modify coupon display
3. **DOM Manipulation**: Enhancing existing coupon input fields with gift card balance information

## Styling

The gift card balance information is styled using CSS classes:

- `.leat-giftcard-balance`: Base class for the balance display
- `.leat-giftcard-balance.success`: When a valid gift card balance is displayed
- `.leat-giftcard-balance.error`: When there's an error checking the balance

## How It Works

1. When a customer enters a gift card code in the coupon field, the component checks if it's a valid gift card
2. If it is, it displays the balance below the coupon field
3. When a gift card coupon is applied, the balance is shown next to the coupon in the cart/checkout summary
4. The component automatically updates when coupons are added or removed

## License

This component is part of the Leat CRM plugin and is subject to the same licensing terms.
