=== XT Points & Rewards for WooCommerce ===

Plugin Name: XT Points & Rewards for WooCommerce
Contributors: XplodedThemes
Author: XplodedThemes
Author URI: https://www.xplodedthemes.com
Tags: points rewards, woocommerce points, woocommerce rewards, woocommerce loyalty, woocommerce coupons, woocommerce points an rewards, woocommerce discounts, points, rewards, customer loyalty, coupons, discounts
Requires at least: 4.6
Tested up to: 6.6
Stable tag: 1.7.5
Requires PHP: 5.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Points and Rewards for WooCommerce that lets you reward your customers for purchases and other actions with points that can be redeemed for discounts.

== Description ==

A WooCommerce extension that lets you reward your customers for purchases and other actions with points that can be redeemed for discounts. Easily set how many points customers should earn for each dollar spent and how many points can be redeemed for a specific discount amount. Points can be awarded by product, category, or global level, and you can also control the maximum discount available when redeeming points.

**Demo**

[https://demos.xplodedthemes.com/woo-points-rewards/](https://demos.xplodedthemes.com/woo-points-rewards/)

**Free Version**

- Set the conversion rate (spend/points) to set the number of points customers can collect for each purchase
- Admin can view a list of users / points collected with purchases
- Admin can update the number of points earned by users
- Users can view points earned so far in "My account" page
- Users can redeem their points on the cart & checkout page
- Assign points only when the order is completed
- Automatically removes points assigned to orders that are later cancelled or refunded
- Option to reset points history for all or specific customers
- Apply points for existing orders before the plugin was installed
- Insert "My points" link in customers' account page

**Premium Features**

- All Free Features
- Admin can BULK update the number of points earned by users
- Ability to filter Points Log by event type and by month
- Partially redeem points on the cart & checkout page
- Set a maximum amount for discounts (customisable globally, per category or single product)
- Assign a specific number of points that can be earned for each simple or variable product to the users who purchase on your store.
- Override points awarding rules on category and product level
- Insert fully customizable points badges on your shop products to highlight how many points can a customer earn on purchase.
- Assign extra points when the following actions occur:
    - Store registration
    - First order placed
    - Product review
    - Specific spend threshold reached
    - Specific number of points collected
    - User's birthday

- Show how many points can be earned when buying a product on the product page
- Show points in order details and in the Order confirmation email
- Edit all labels and messages shown to users
- Shortcode that allows showing the points history to users
- Possibility to set a percent discount based on the product price
- Possibility to set a minimum amount of discount under which users can’t redeem their points
- When creating a coupon, assign a percentage which modifies how points are earned when using the coupon.
- Allow the shop manager to edit user points
- Automatically send email notifications to customers whenever their points gets updated. Can be tuned ON or OFF for each action: https://d.pr/i/FOM87E.

**Compatible With <a target="_blank" href="https://xplodedthemes.com/products/woo-floating-cart/">Woo Floating Cart</a>**
**Compatible With <a target="_blank" href="https://xplodedthemes.com/products/woo-quick-view/">Woo Quick View</a>**

**Translations**

- English - default

*Note:* All our plugins are localized / translatable by default. This is very important for all users worldwide. So please contribute your language to the plugin to make it even more useful.

== Installation ==

Installing "Points & Rewards for WooCommerce" can be done by following these steps:

1. Download the plugin from the customer area at "XplodedThemes.com" 
2. Upload the plugin ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
3. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

#### V.1.7.5 - 14.09.2024
- **update**: Freemius SDK update v2.8.0
- **fix**: Security Fix

#### V.1.7.4 - 29.07.2024
- **update**: Freemius SDK update v2.7.3
- **support**: WordPress 6.6
- **support**: WooCommerce 9.1

#### V.1.7.3 - 01.02.2024
- **update**: Freemius SDK update v2.6.2
- **support**: WordPress 6.4

#### V.1.7.2 - 13.09.2023
- **update**: Patched a persistent bug that was causing points email notifications to always be sent, despite being set to disabled.

#### V.1.7.0 - 25.08.2023
- **update**: Added WooCommerce HPOS Support

#### V.1.6.6 - 24.07.2023
- **update**: Freemius SDK update v2.5.10

#### V.1.6.5 - 18.04.2023
- **support**: Fixed conflict with BETheme Page Builder

#### V.1.6.4 - 08.03.2023
- **support**: Removed xt-observers-polyfill script, since the plugin does not support old browsers anymore.
- **update**: XT Framework update

#### V.1.6.3 - 17.01.2023
- **fix**: Minor fixes
- **update**: XT Framework update

#### V.1.6.2 - 23.12.2022
- **fix**: Fixed issue with Points Expiration automated schedule.
- **fix**: Minor fixes
- **fix**: Temporarily disable sending email notifications when bulk applying points to previous orders within the Admin Tools Settings.
- **update**: XT Framework update

#### V.1.6.0 - 22.12.2022
- **new**: **pro** Added new Email Notifications settings. https://d.pr/i/FOM87E. You now have the option to send an email notification to your customers whenever their points get's updated by a specific action.
- **new**: **pro** Added new email template: **/templates/emails/points-update.php** can be overridden within the theme. Template preview: https://d.pr/i/remvoN
- **fix**: Fixed issue with "Manage Points" update button when the backend is loaded with a different language.
- **fix**: Update Points / Point strings within the backend to reflect the labels set within the settings.
- **update**: Update language file
- **update**: XT Framework update

#### V.1.5.0 - 04.10.2022
- **fix**: Fixed issue with "Manage Points" update button (Free version only)

#### V.1.4.9 - 28.09.2022
- **new**: Added option to enable the plugin only for selected **user roles**. https://d.pr/i/2ylSl2
- **fix**: Fixed issue with **product variation level** points settings not saving
- **fix**: Fixed issue with [xt_woopr_messages] shortcode not appearing on some themes
- **support**: Added support for the native woocommerce cart / checkout blocks
- **update**: Update language file
- **update**: XT Framework update

#### V.1.4.8 - 27.09.2022
- **fix**: Fixed issue with **product level points value** when it is set to 0
- **fix**: Ensure partial redemptions meet the Minimum Points Discount.
- **fix**: Fixed intermittent issue with redeemed points not being substracted from user points in some cases.
- **fix**: Minor fixes

#### V.1.4.7 - 12.09.2022
- **fix**: Fixed issue with points calculation and rounding.
- **fix**: Minor fixes
- **update**: XT Framework update

#### V.1.4.6 - 19.05.2022
- **new**: Display order link / comment link within frontend points table similar to the backend. https://d.pr/i/KhgBLd
- **update**: XT Framework update
- **fix**: Minor CSS Fixes

#### V.1.4.5 - 25.04.2022
- **new**: Added new Date Format option for the Birthday field. https://d.pr/i/qGdPTZ
- **new**: Added the ability to quickly change months / years within the birthday datepicker.
- **fix**: Earn / Redeem Points Message Visibility for "Shop Pages" will also be applied to product category pages
- **fix**: If the "Variable Product Page Message" setting is empty, hide the message completely insead of showing an empty message box.

#### V.1.4.4 - 23.03.2022
- **update**: XT Framework update
- **support**: Better support for WoodMart theme
- **fix**: Minor CSS Fixes

#### V.1.4.3 - 03.03.2022
- **fix**: Freemius Security Fix
- **update**: XT Framework update

#### V.1.4.2 - 27.01.2022
- **support**: Support XT Floating Cart v2.6
- **update**: XT Framework update

#### V.1.4.1 - 04.01.2022
- **new**: Added 2 filters **xt_woopr_singlular_points_label** and **xt_woopr_plural_points_label**. This will allow modifying / translating the points label manually.
- **update**: XT Framework update

#### V.1.4.0 - 16.11.2021
- **update**: Modify plugin name to avoid trademark violation with WooCommerce
- **fix**: Fix error in backend
- **update**: XT Framework update

#### V.1.3.9 - 09.11.2021
- **support**: Better support and fixes for WooCommerce Currency Switcher plugin

#### V.1.3.8 - 21.10.2021
- **support**: Added support for WooCommerce Currency Switcher (https://currency-switcher.com/)

#### V.1.3.7 - 13.09.2021
- **fix**: Settings button not visible in some cases.
- **fix**: Many fixes and imporovements
- **new**: Added action earned points to order notes

#### V.1.3.6 - 04.08.2021
- **fix**: Do not show points for unpurchasable products such as external and affiliate products.

#### V.1.3.5 - 06.07.2021
- **update**: XT Framework update

#### V.1.3.4 - 15.06.2021
- **fix**: Fixed error in admin points log after reviewing a product.

#### V.1.3.3 - 02.06.2021
- **update**: XT Framework update

#### V.1.3.2 - 20.05.2021
- **fix**: Fix error: Cannot use object of type stdClass as array. Happens with some themes.
- **update**: XT Framework update

#### V.1.3.1 - 23.04.2021
- **new**: Added ability to modify the "Earned Points" messages displayed within the "New Order" email sent to both customers and admins.
- **fix**: Fixed minor caching issue.

#### V.1.3.0 - 05.04.2021
- **new**: Added Earned Points message within new order email notification.
- **support**: WordPress 5.7 compatibility.
- **support**: Compatibility with PHP 8.0.
- **support**: Add compatibility with WooCommerce Pre-Orders.
- **support**: Remove legacy code.
- **support**: Point to general settings link when checking coupon setting.
- **fix**: Variable product Points not updated when Points Conversion changed.
- **fix**: Points not redeemed on subscription renewal orders.
- **fix**: Unable to apply points from the checkout page to an order with a subscription product.
- **fix**: Prevent fatal error when viewing coupons in admin with Subscriptions.
- **fix**: Update Min Points Discount tooltip to more accurately describe the setting.
- **fix**: fix integration with automated taxes plugins.
- **fix**: Stop using legacy filter to solve rounding issues.
- **fix**: Maximum points discount is not taken into account when users have more points available.
- **fix**: Use proper escape for attributes.
- **fix**: Set maximum amount of points when redeeming a partial amount.
- **fix**: Points redemption showing warning on cart when non-numeric value is used for points value.
- **fix**: Available points incorrect for non-numeric max setting.
- **fix**: Admin: Only display points for users from current site in Multisite.

#### V.1.2.5 - 31.03.2021
- **support**: Multisite - Network Level License Management
- **update**: XT Framework update

#### V.1.2.4 - 23.03.2021
- **fix**: XT Framework update / fixes

#### V.1.2.3 - 22.03.2021
- **new**: Added extensive options to customize the product Points Badge
- **fix**: Minor Fixes
- **update**: XT Framework update

#### V.1.2.2 - 03.03.2021
- **update**: XT Framework update

#### V.1.2.0 - 02.03.2021
- **new**: Added new Points Badge option. Insert a points badge on your shop products to highlight how many points can a customer earn on purchase.
- **update**: XT Framework update

#### V.1.1.9 - 08.02.2021
- **fix**: Minor Fixes
- **update**: XT Framework update

#### V.1.1.8 - 28.01.2021
- **enhance**: **pro** Partial redeem input added within the notice instead of using an alert box
- **fix**: **pro** Make shortcode message always visible by ignoring visibility settings
- **fix**: Minor CSS Fixes

#### V.1.1.7 - 21.12.2020
- **fix**: Minor CSS Fixes

#### V.1.1.6 - 10.12.2020
- **support**: Added support for Loco Translate by adding a loco.xml bundle config file.
- **update**: Updated translation file

#### V.1.1.5 - 30.11.2020
- **fix**: CSS Fixes
- **fix**: Fixed issue with the earn & redeem messages page visibility option not being applied correctly
- **update**: The correct shortcode to display points related messages has been changed to [xt_woopr_earn_messages]. The old [xt_woopr_earn_message] will continue to work for now.
- **update**: XT Framework update
- **support**: Better support for XT Woo Floating Cart

#### V.1.1.4 - 29.10.2020
- **new**: Added an option to select on which pages among (shop, cart & checkout) to display the earn & redeem messages.
- **new**: Added {points_value} variable (monetary value) to be used in messages.
- **enhance**: Cache duplicated queries.
- **update**: XT Framework update

#### V.1.1.3 - 27.10.2020
- **fix**: Fixed issue with the "Earn Message" not displaying on product page when a variation (loaded via ajax) is selected.
- **update**: XT Framework update

#### V.1.1.2 - 26.10.2020
- **new**: Replaced long coupon label with "Points Redemption". Also added an option to modify the label.
- **fix**: Fix display issue for the redeem message on the checkout page.
- **update**: XT Framework update

#### V.1.1.1 - 15.10.2020
- **fix**: Redeem points action using Ajax without reloading the page

#### V.1.1.0 - 14.10.2020
- **fix**: Minor CSS fixes
- **support**: Better theme support
- **new**: Display earning message on Shop page as well.
- **new**: **pro** Added [xt_woopr_earn_message] shortcode for displaying the Earn X Points Message
- **enhance**: Messages on product page will now be styles as a woocommerce info notification that should inherit theme styles
- **update**: XT Framework update

#### V.1.0.9 - 10.10.2020
- **fix**: Fix issue with points messages not being displayed on cart / checkout on some themes
- **fix**: Minor CSS fixes
- **new**: Messages can now also be modified within the free version.

#### V.1.0.8 - 07.10.2020
- **new**: XT Framework System Status will now show info about the active theme as well as XT Plugin templates that are overridden by the theme. Similar to woocommerce, it will now be easier to know which plugin templates are outdated.

#### V.1.0.7 - 23.09.2020
- **fix**: Replaced deprecated function WC()->cart->coupons_enabled() with wc_coupons_enabled()
- **fix**: Minor fixes

#### V.1.0.6 - 15.07.2020
- **new**: Added a "How to earn points?" table on the "My Points" page
- **new**: **pro** Added 3 different shortcodes. [xt_woopr_my_points], [xt_woopr_my_points_log], [xt_woopr_points_legend]
- **fix**: Minor fixes

#### V.1.0.5 - 29.01.2020
- **fix**: Fixed error message with free version

#### V.1.0.4 - 29.01.2020
- **fix**: Fixed issue with plugin TextDomain not being loaded properly
- **update**: Updated translation files

#### V.1.0.3 - 16.01.2019
- **fix**: **pro** Fix issue with birthday field not showing on checkout page when it should
- **fix**: Minor fixes
- **fix**: Fix conflict with myCred and YITH WooCommerce Points and Rewards

#### V.1.0.2 - 10.01.2019
- **fix**: Minor fixes

#### V.1.0.1 - 10.01.2019
- **update**: XT Framework update

#### V.1.0.0 - 09.01.2019
- **Initial**: Initial Version

