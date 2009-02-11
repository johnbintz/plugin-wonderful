<?php
/*
Plugin Name: Plugin Wonderful
Plugin URI: http://www.coswellproductions.com
Description: Easily embed a Project Wonderful publisher's advertisements.
Version: 0.1
Author: John Bintz
Author URI: http://www.coswellproductions.org/wordpress/

Copyright 2009 John Bintz  (email : jcoswell@coswellproductions.org)

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

require_once('classes/PWAdboxesClient.php');

define('PLUGIN_WONDERFUL_XML_URL', 'http://www.projectwonderful.com/xmlpublisherdata.php?publisher=%d');

class PluginWonderful {
  var $messages, $adboxes_client, $publisher_info, $member_id;

  function PluginWonderful() {
    $this->messages = array();
    $this->adboxes_client = new PWAdboxesClient();
    $this->publisher_info = false;

    if ($member_id = get_option('plugin-wonderful-memberid')) {
      $this->publisher_info = $this->adboxes_client->get_ads($member_id);
    }

    if (!empty($_POST)) { $this->handle_action(); }
  }

  function render_widget($options, $adboxid) {
    if ($this->publisher_info !== false) {
      foreach ($this->publisher_info->adboxes as $adbox) {
        if ($adbox->adboxid == $adboxid) {

          if (get_option("plugin-wonderful-use-standardcode") == 1) {
            echo $adbox->standardcode;
          } else {
            echo $adbox->advancedcode;
          }
          break;
        }
      }
    }
  }

  function set_up_menu() {
    add_options_page('Plugin Wonderful', __("Plugin Wonderful", 'plugin-wonderful'), 5, __FILE__, array($this, "plugin_wonderful_main"));
  }

  function set_up_widgets() {
    if ($this->publisher_info !== false) {
      if (($widgets = $this->publisher_info->get_sidebar_widget_info()) !== false) {
        foreach ($widgets as $widget_info) {
          extract($widget_info);
          wp_register_sidebar_widget($id, $name, array($this, 'render_widget'), "", $options['adboxid']);
        }
      }
    }
  }

  function handle_activation() {
    $this->adboxes_client->initialize();
  }

  function plugin_wonderful_main() {
    $this->get_view(__FUNCTION__);
  }

  function show_messages() {
    if (count($this->messages) > 0) {
      echo '<div id="message" class="updated fade below-h2">';
        foreach ($this->messages as $message) { echo '<p>' . $message . '</p>'; }
      echo '</div>';
    }
  }

  function _create_target($name, $source) {
    return ABSPATH . PLUGINDIR . '/' . dirname(plugin_basename(__FILE__)) . "/{$source}/{$name}.php";
  }

  function get_view($function_name) {
    $target = $this->_create_target(str_replace('plugin_wonderful_', '', $function_name), "views");
    if (file_exists($target)) {
      echo '<div class="wrap">';
        echo '<div id="icon-edit" class="icon32"><br /></div>';
        echo '<h2>' . __("Plugin Wonderful", 'plugin-wonderful') . '</h2>';

        $this->show_messages();

        include($target);

        echo '<div style="margin-top: 20px; border-top: solid #E3E3E3 1px">';
          echo 'Plugin Wonderful Version 0.1 by <a href="mailto:john@coswellproductions.com">John Bintz</a> | ';
          echo '<a href="http://www.projectwonderful.com/login.php">Manage your Project Wonderful publisher account</a>';
        echo '</div>';
      echo '</div>';
    } else {
      die("View not found: " . str_replace('plugin-wonderful_', '', $function_name));
    }
  }

  function handle_action() {
    $action = "handle_action_" . str_replace("-", "_", preg_replace('#[^a-z\-]#', '', strtolower($_POST['action'])));
    if (method_exists($this, $action)) { call_user_func(array($this, $action)); }
  }

  function handle_action_rebuild_database() {
    $this->adboxes_client->destroy();
    $this->adboxes_client->initialize();

    $this->messages[] = __("Adbox database destroyed and rebuilt.", 'plugin-wonderful');

    if (get_option('plugin-wonderful-memberid') != "") {
      if (($result = file_get_contents(sprintf(PLUGIN_WONDERFUL_XML_URL, (int)get_option('plugin-wonderful-memberid')))) !== false) {
        $this->publisher_info = new PublisherInfo();
        if ($this->publisher_info->parse($result)) {
          $this->adboxes_client->post_ads($this->publisher_info);
          $this->messages[] = sprintf(__('Adbox information redownloaded.', 'plugin-wonderful'), (int)$_POST['memberid']);
        } else {
          $this->messages[] = __("Unable to parse publisher data from Project Wonderful.", 'plugin-wonderful');
          $this->publisher_info = false;
        }
      } else {
        $this->messages[] = __("Unable to read publisher data from Project Wonderful.", 'plugin-wonderful');
        $this->publisher_info = false;
      }
    }
  }

  function handle_action_change_memberid() {
    if (trim($_POST['memberid'])) {
      if (trim($_POST['memberid']) === (string)(int)$_POST['memberid']) {
        if (($result = file_get_contents(sprintf(PLUGIN_WONDERFUL_XML_URL, (int)$_POST['memberid']))) !== false) {
          $this->publisher_info = new PublisherInfo();
          if ($this->publisher_info->parse($result)) {
            update_option('plugin-wonderful-memberid', (int)$_POST['memberid']);
            $this->adboxes_client->post_ads($this->publisher_info);
            $this->messages[] = sprintf(__('Member number changed to %s and adbox information redownloaded.', 'plugin-wonderful'), (int)$_POST['memberid']);
          } else {
            $this->messages[] = __("Unable to parse publisher data from Project Wonderful.", 'plugin-wonderful');
            update_option('plugin-wonderful-memberid', "");
            $this->publisher_info = false;
          }
        } else {
          $this->messages[] = __("Unable to read publisher data from Project Wonderful.", 'plugin-wonderful');
          update_option('plugin-wonderful-memberid', "");
          $this->publisher_info = false;
        }
      } else {
        $this->messages[] = __("Member numbers need to be numeric.", 'plugin-wonderful');
        update_option('plugin-wonderful-memberid', "");
        $this->publisher_info = false;
      }
    } else {
      $this->messages[] = __("Existing adbox information removed.", 'plugin-wonderful');
      update_option('plugin-wonderful-memberid', "");

      $this->publisher_info = false;
    }

    update_option('plugin-wonderful-use-standardcode', isset($_POST['use-standardcode']) ? "1" : "0");
  }
}

$plugin_wonderful = new PluginWonderful();

add_action('admin_menu', array($plugin_wonderful, 'set_up_menu'));
add_action('init', array($plugin_wonderful, 'set_up_widgets'));
register_activation_hook(__FILE__, array($plugin_wonderful, 'handle_activation'));

function the_project_wonderful_ad($adboxid) {
  global $plugin_wonderful;

  $plugin_wonderful->render_widget(array(), $adboxid);
}

?>
