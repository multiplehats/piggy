=== Leat CRM ===
Contributors: leat
Donate link: https://leat.com
Tags: loyalty program, rewards, loyalty, vouchers, marketing automation
Tested up to: 6.7.1
Stable tag: 0.9.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Create and manage customer loyalty programs with points, rewards, and automated marketing - works both online and in-store.

== Description ==

Enhance Your Customer Experience with Leat!

[youtube https://www.youtube.com/watch?v=3udRTILYGuE]

Leat's all-in-one platform empowers you to:

* Quickly set up a fully customized loyalty program and rewards, with no coding required
* Effortlessly engage customers using targeted vouchers and promotions
* Drive growth with real-time analytics and insights
* Seamlessly manage your loyalty program both online and offline

= Key Features =

* **Points & Rewards Program** - Create a flexible points system where customers earn points for purchases
* **Custom Rewards** - Design attractive rewards that keep customers coming back
* **Automated Marketing** - Send targeted emails and notifications based on customer behavior
* **Real-time Analytics** - Track program performance and customer engagement
* **WooCommerce Integration** - Seamlessly works with your existing store
* **Multi-channel Support** - Works both online and in physical stores

= External Services =

This plugin requires an API connection to [Leat](https://www.leat.com) and its business portal ([business.leat.eu](https://business.leat.com)) for core functionality including reward management, customer tracking, and loyalty point calculations.

**Data transmitted includes**:
- Customer information (email, name, order history, purchase categories)
- WooCommerce order data (order totals, products purchased, dates)
- Store configuration data
- Reward and voucher definitions
- Store performance metrics and analytics

All data is transmitted securely via HTTPS to Leat's servers in compliance with GDPR and other applicable privacy regulations. Data processing occurs on both leat.com and business.leat.eu domains.

Users of this plugin should ensure they comply with the terms and privacy policies of Leat before using this plugin:

- [Terms of Service](https://www.leat.com/legal/terms-and-conditions)
- [Privacy Policy](https://www.leat.com/legal/privacy-policy)

== Screenshots ==

1. The plugin settings dashboard

== Installation ==

1. Upload `leat.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add your API key in the plugin settings.
4. Sync your rewards and vouchers with Leat to get started.
5. Use the [leat_dashboard] shortcode to render the loyalty dashboard.
6. Optionally use [leat_reward_points] shortcode to display customer points anywhere on your site.

== Frequently Asked Questions ==

= How do I get an API key? =

Sign up for a Leat account at [business.leat.eu](https://business.leat.com). Once logged in, navigate to Settings > API Integration to find your API key.

= Can I customize the loyalty dashboard appearance? =

Yes! The dashboard can be customized using CSS.

= Does this work with my existing WooCommerce store? =

Yes, Leat integrates seamlessly with WooCommerce.

= How are points calculated? =

By default, customers earn 1 point per currency unit spent (e.g., 1 point per $1). This can be customized in your Leat business portal, where you can also set up multipliers for specific products or categories.

= Is this GDPR compliant? =

Yes, Leat is fully GDPR compliant. We process all data in accordance with EU privacy regulations and provide the necessary tools for handling data subject requests. See our [Privacy Policy](https://www.leat.com/legal/privacy-policy) for more details.

= What happens if I uninstall the plugin? =

Your customer data and loyalty program settings are safely stored in your Leat account. If you reinstall the plugin later, you can simply reconnect using your API key and all data will be restored.

= What shortcodes are available? =

1. [leat_dashboard] - Displays the full loyalty program dashboard
2. [leat_reward_points] - Shows customer's current point balance. Supports these attributes:
   - format: Customize text (e.g., "You have {{ credits }} {{ credits_currency }}")
   - hide_zero: Set to "yes" to hide when points are zero
   - wrapper_class: Add custom CSS classes
   - user_id: (Admin only) Show points for a specific user

== Changelog ==

= 0.6.0 =

* Feat: Added automatic voucher sync between Leat and WooCommerce
  - Vouchers created in Leat are automatically synced as WooCommerce coupons
  - Real-time sync when vouchers are redeemed, updated, or deleted
  - Maintains contact-specific restrictions and custom attributes
  - Handles expiration dates and usage limits
  - Note: Activation date is not yet supported, only expiration date.

= 0.6.1 =

* Bug fix: fixed an issue where URL parameters in script tags could cause conflicts with caching plugins by stripping parameters before comparison
* Enhancement: Added span element with class 'leat-credits' around credits text in customer dashboard for improved styling control
* Bug fix: Improved coupon visibility logic to prevent coupons from being displayed to customers under certain unintended conditions

= 0.6.2 =

* Bug fix: fixed an issue where it was not possible to claim spend rule under certain conditions

= 0.6.3 =

* Bug fix: Fixed gift card completion message showing on all orders instead of only orders containing gift cards
* Bug fix: Fixed reward images not syncing properly for newer rewards
* Enhancement: When a coupon is removed from the cart for a free product, the free product will also be removed for WC Store API requests
* Enhancement: After claiming a reward, the dashboard now scrolls up to the coupon section
* Feature: Added new setting to control visibility of the "Join Program" CTA on the dashboard
* Enhancement: Added automatic detection of WooCommerce registration settings to hide join CTA when registration is disabled
* Feature: Added new [leat_reward_points] shortcode for displaying customer points anywhere on your site

= 0.7.0 =

* Enhancement: Added new user attributes:
  * Syncs WordPress multisite blog memberships for users
  * Syncs user's last order ID
* Enhancement: Webhooks automatically resync when site domain changes to maintain functionality
* Enhancement: Rewards and promotions automatically sync when API key is changed
* Enhancement: Added direct link to API key generation page in settings for easier access
* Enhancement: Improved visibility of the save button
* Enhancement: Vouchers are now automatically synced when promotion rules are published
* Security: API key is now masked for users without manage_options capability, showing only last 4 characters
* Enhancement: Improved coupon code generation
* Bug fix: Fixed displayed credit amount when `{{ credits }}` placeholder was not being used.

= 0.8.0 =

* Feature: Added support for custom WooCommerce order statuses
  - Custom statuses must follow WooCommerce naming convention with 'wc-' prefix to be recognized
* Feature: Added loyalty program support for WooCommerce blocks checkout
  - Automatically links orders to Leat contacts during checkout
  - Properly syncs customer attributes and rewards points
  - Maintains loyalty account tracking for both guest and registered users
* Feature: Enhanced webhook management system
  - Added new webhook management interface in admin dashboard
  - Improved support for voucher creation webhooks
  - Improved webhook synchronization and error handling
  - Added automatic webhook status monitoring
* Bug fix: Fix an issue where webhooks, rewards and promotions would always sync on settings save.

= 0.9.0 =

* Feature: Personalized rewards display - Only shows rewards that match the contact's eligibility based on visibility filters configured in the Leat.com dashboard
* Enhancement: Improved rewards sorting - Rewards are now displayed in descending order by credit cost (high to low) for better user experience
* Enhancement: Added logs submenu to easier access debug logs.

= 0.9.1 =

* Enhancement: Improve redirect for customers who choose "Login" or "Join program" in the dashboard.