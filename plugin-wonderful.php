<?php
/*
Plugin Name: Plugin Wonderful
Plugin URI: http://www.coswellproductions.com/wordpress/wordpress-plugins/
Description: Easily embed a Project Wonderful publisher's advertisements.
Version: 0.4.4
Author: John Bintz
Author URI: http://www.coswellproductions.org/wordpress/

Copyright 2009 John Bintz  (email : john@coswellproductions.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

foreach (glob(dirname(__FILE__) . '/classes/*.php') as $file) { require_once($file); }
// require_once('FirePHPCore/fb.php');

define('PLUGIN_WONDERFUL_XML_URL', 'http://www.projectwonderful.com/xmlpublisherdata.php?publisher=%d');
define('PLUGIN_WONDERFUL_UPDATE_TIME', 60 * 60 * 12); // every 12 hours

$plugin_wonderful = new PluginWonderful();

add_action('admin_menu', array($plugin_wonderful, 'set_up_menu'));
add_action('init', array($plugin_wonderful, 'init'));
add_filter('the_excerpt_rss', array($plugin_wonderful, 'insert_rss_feed_ads'));
add_filter('the_content', array($plugin_wonderful, 'inject_ads_into_body_copy'));

register_activation_hook(__FILE__, array($plugin_wonderful, 'handle_activation'));

?>