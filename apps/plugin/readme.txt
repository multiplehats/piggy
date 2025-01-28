=== Leat CRM ===
Contributors: leat
Donate link: https://leat.com
Tags: loyalty program, rewards, customer loyalty, vouchers, marketing automation
Requires at least: 6.0
Tested up to: 6.7.1
Requires PHP: 8.0
Stable tag: 0.5.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

### Customer loyalty that works in-store and online.

Enhance Your Customer Experience with Leat!

[youtube https://www.youtube.com/watch?v=3udRTILYGuE]

Leat's all-in-one platform empowers you to:

● Quickly set up a fully customized loyalty program and rewards, with no coding required.
● Effortlessly engage customers using targeted vouchers and promotions integrated with
automated marketing tools.
● Drive growth with real-time analytics and insights, ensuring your customers stay
connected and engaged.
● Seamlessly manage your loyalty program both online and offline with Leat's extensive
integrations, eliminating any gaps in your customer experience.

### External services

This plugin requires an API connection to [Leat](https://www.leat.com) and it's business portal ([business.leat.eu](https://business.leat.eu)) for core functionality including reward management, customer tracking, and loyalty point calculations.

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

1. The main dashboard showing customer loyalty points and available rewards.

== Installation ==

1. Upload `leat.zip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add your API key in the plugin settings.
4. Sync your rewards and vouchers with Leat to get started.
5. Use the [leat_dashboard] shortcode to render the loyalty dashboard.

== Frequently Asked Questions ==

= How do I get an API key? =

Sign up for a Leat account at [business.leat.eu](https://business.leat.eu). Once logged in, navigate to Settings > API Integration to find your API key.

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