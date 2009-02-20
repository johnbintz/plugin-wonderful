=== Plugin Wonderful ===
Contributors: johncoswell
Tags: ads, sidebar, widget
Requires at least: 2.7
Tested up to: 2.7.1
Stable tag: 0.4.2
Donate link: http://www.coswellproductions.com/wordpress/wordpress-plugins/

Plugin Wonderful lets Project Wonderful publishers quickly and easily add their adboxes to thier WordPress site.

== Description ==

Plugin Wonderful downloads your adbox information from Project Wonderful and creates a series of widgets that can easily be added to your sidebars. It also adds a new template tag, `the_project_wonderful_ad()`, that lets you embed Project Wonderful ads directly into your site. Adbox code is downloaded straight from Project Wonderful, so your adboxes are always displayed correctly. You can also easily activate new ads without having to modify your theme.

== Installation ==

Copy the plugin-wonderful folder to your wp-content/plugins folder and activate it from Plugins -> Installed. Activating the plugin creates a new database table, `{prefix}_pw_adboxes`, that you can safely remove if you are no longer using the plugin.

== Frequently Asked Questions ==

= Where do I get my member number? =

Log in to your Project Wonderful account and view your profile by clicking on your profile picture on the right. Your member number is the first item listed in your profile.

= How do Template Tag Identifiers work? =

If you want to refer to an ad by an arbitrary name, rather than the adbox id, you can give the specific ad a tag, and then use that tag instead of the adbox id in `the_project_wonderful_ad()`. For example, tag an ad with the name "header" and you can refer to it in your theme with:

`the_project_wonderful_ad('header')`

= How robust is placing ads in the RSS feed? =

Project Wonderful does support placing ads in the RSS feed. In this case, all of the JavaScript is stripped out, since many RSS feed readers don't support JavaScript in the feed. The ads are placed above any excerpts in the feed. Some readers may not like the markup used. Until further testing is done, there are no good answers on how well PW ads delivered by Plugin Wonderful will perform.

Additonally, the ad that you use in your RSS feed will need to be accessible by Project Wonderful's ad verification crawlers, so you'll need to place the ad somewhere on your site before you can use it in your RSS feed.

= How often is adbox information redownloaded? =

Plugin Wonderful checks your Project Wonderful account every 12 hours for adbox changes. If there are any, it will apply the changes to your adboxes.

= I am a theme author who wants to include pre-built hooks for Project Wonderful ads. How do I safely add those hooks? =

Wrap the call to `the_project_wonderful_ad()` in a `function_exists()` call:

`<?php if (function_exists('the_project_wonderful_ad')) { the_project_wonderful_ad('header'); } ?>`
