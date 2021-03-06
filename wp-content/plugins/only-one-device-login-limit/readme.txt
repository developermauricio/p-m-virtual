=== Only one device login limit ===
Contributors: addonspress, acmeit, codersantosh, yamchhetri
Donate link: https://www.addonspress.com/
Tags: limit login, one device, login, signin, logout, signout, interval, duration, automatic, auto logout, idle time
Requires at least: 4.9
Tested up to: 5.7.1
Stable tag: 1.2.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Limit login to one device at a time for a user. Configured options from the admin

== Description ==

This plugin limit login to one device at a time for a user.
If same user login from another device, that user won't be allow to login.
Admin can setup 'Already login message' for that user.

If the user has been inactive for too long, then the user is automatically logged out and that user allow to login again either from same device or another device.
Admin can setup 'Auto Logout Duration' for users.

It tracks the users activity like user status ( Active/InActive ) and Last active time.
Admin can view user status from  WP Admin > Users > All users.  From "User Status" column, user current status can be viewed.

Admin can setup only one device login limit plugin from WP Admin > Settings > Coder limit login

Note : This plugin is compatible with most of the membership plugins.
If you find any issues, please use [support forum](https://wordpress.org/support/plugin/only-one-device-login-limit) or visit [Only one device login limit](https://www.addonspress.com/wordpress-plugins/only-one-device-login-limit/) to report.

== Installation ==

**Method 1: Automatic Plugin Installation**

1. Login to admin panel,Go to Plugins => Add New.
2. Search for "Only one device login limit" and install it.
3. Once you install it, activate it

**Method 2: Manual Plugin Installation**

1. Upload the plugin's folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. From WP Admin go to Settings > Coder limit login and configure as you want.


== Frequently Asked Questions ==

= What if some how I logout from this plugin? =

Go to the plugin's folder to the `/wp-content/plugins/` directory and rename "only-one-device-login-limit" folder.
After that if you want to activate plugin again, don't forget to rename again to "only-one-device-login-limit"

Need any help, please use [support forum](https://wordpress.org/support/plugin/only-one-device-login-limit) or visit [Only one device login limit](https://www.addonspress.com/wordpress-plugins/only-one-device-login-limit/)

== Screenshots ==

1. Already login message
2. Settings > Coder limit login
2. User status

== Changelog ==

= 1.2.1 ??? 2021-04-21 =

* Updated : Latest version test

= 1.2 ??? 2020-05-03 =

* Updated : Latest version test
* Added : Contributor

= 1.1 =

* Make compatible with "Log Out of All Sessions" button

* Check and fixed compatibility issues with membership plugins

* Fixed issues

= 1.0 =

* Initial version
