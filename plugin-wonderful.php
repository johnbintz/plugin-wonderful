<?php
/*
Plugin Name: Plugin Wonderful
Plugin URI: http://www.coswellproductions.com/wordpress/wordpress-plugins/
Description: Easily embed a Project Wonderful publisher's advertisements.
Version: 0.4.3
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
// require_once('FirePHPCore/fb.php');

define('PLUGIN_WONDERFUL_XML_URL', 'http://www.projectwonderful.com/xmlpublisherdata.php?publisher=%d');
define('PLUGIN_WONDERFUL_UPDATE_TIME', 60 * 60 * 12); // every 12 hours

class PluginWonderful {
  var $messages, $adboxes_client, $publisher_info, $member_id;

  function PluginWonderful() {
    $this->messages = array();
    $this->adboxes_client = new PWAdboxesClient();
    $this->publisher_info = false;

    if ($member_id = get_option('plugin-wonderful-memberid')) {
      $this->publisher_info = $this->adboxes_client->get_ads($member_id);

      if ((get_option('plugin-wonderful-last-update') + PLUGIN_WONDERFUL_UPDATE_TIME) < time()) {
        if (($result = file_get_contents(sprintf(PLUGIN_WONDERFUL_XML_URL, (int)get_option('plugin-wonderful-memberid')))) !== false) {
          $this->publisher_info = new PublisherInfo();
          if ($this->publisher_info->parse($result)) {
            $this->adboxes_client->post_ads($this->publisher_info);
            update_option('plugin-wonderful-last-update', time());
          }
        }
      }
    }

    $result = get_option('plugin-wonderful-database-version');
    if (empty($result) || ($result < PLUGIN_WONDERFUL_DATABASE_VERSION)) {
      if ($this->adboxes_client->initialize(true)) {
        update_option('plugin-wonderful-database-version', PLUGIN_WONDERFUL_DATABASE_VERSION);
      } else {
        $this->messages[] = "Unable to update database schema!";
      }
    }

    if (!empty($_POST)) { $this->handle_action(); }
  }

  function insert_rss_feed_ads($content) {
    if (is_feed()) {
      foreach ($this->publisher_info->adboxes as $adbox) {
        if ($adbox->in_rss_feed == 1) {
          if (preg_match("#<noscript>(.*)</noscript>#mis", $adbox->advancedcode, $matches) > 0) {
            echo $matches[1];
          }
        }
      }
    }
    return $content;
  }

  function insert_activation_ad() {
    $result = get_option('plugin-wonderful-activate-ad-code');
    if (!empty($result)) { echo $result; }
  }

  function render_widget($options, $adboxid) {
    if ($this->publisher_info !== false) {
      foreach ($this->publisher_info->adboxes as $adbox) {
        if (($adbox->adboxid == $adboxid) || ($adbox->template_tag_id == $adboxid)) {
          if (get_option("plugin-wonderful-use-standardcode") == 1) {
            $output = $adbox->standardcode;
          } else {
            $output = $adbox->advancedcode;
          }
          if ($adbox->center_widget == 1) {
            $output = "<center>{$output}</center>";
          }
          echo $output;
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
          register_widget_control($id, array($this, 'render_widget_control'), null, null, $options['adboxid']);
        }
      }
    }
  }

  function render_widget_control($adboxid) {
    foreach ($this->publisher_info->adboxes as $box) {
      if ($box->adboxid == $adboxid) {
        echo '<label>';
          echo '<input type="checkbox" name="pw[center][' . $adboxid . ']" ' . (($box->center_widget == 1) ? "checked" : "") . ' /> ';
          echo 'Wrap ad in &lt;center&gt; tags';
        echo '</label>';
        break;
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

      $info = get_plugin_data(realpath(__FILE__));

      echo '<div class="wrap">';
        echo '<div id="icon-edit" class="icon32"><br /></div>';
        echo '<h2>' . __("Plugin Wonderful", 'plugin-wonderful') . '</h2>';

        $this->show_messages();

        include($target);

        echo '<div style="margin-top: 20px; border-top: solid #E3E3E3 1px; overflow: hidden">';
          echo '<form style="float: right; display: inline" action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="3215507"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt=""><img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
          echo sprintf(__('%1$s Version %2$s by %3$s', 'plugin-wonderful'), $info['Title'], $info['Version'], $info['Author']) . ' | ';
          echo __('<a href="http://www.projectwonderful.com/login.php">Manage your Project Wonderful publisher account</a>', 'plugin-wonderful');
          echo '<br style="clear: both" />';
        echo '</div>';
      echo '</div>';
    } else {
      die(__("View not found: ", 'plugin-wonderful') . str_replace('plugin-wonderful_', '', $function_name));
    }
  }

  function handle_action() {
    $action = "handle_action_" . str_replace("-", "_", preg_replace('#[^a-z\-]#', '', strtolower($_POST['action'])));
    if (method_exists($this, $action)) { call_user_func(array($this, $action)); }

    // handle widget updates
    if (isset($_POST['save-widgets'])) { $this->handle_action_save_widgets(); }
  }

  function handle_action_save_widgets() {
    $new_boxes = array();
    foreach ($this->publisher_info->adboxes as $box) {
      if (isset($_POST['pw']['center'][$box->adboxid])) {
        $this->adboxes_client->set_widget_centering($box->adboxid, true);
        $box->center_widget = "1";
      } else {
        $this->adboxes_client->set_widget_centering($box->adboxid, false);
        $box->center_widget = "0";
      }
      $new_boxes[] = $box;
    }
    $this->publisher_info->adboxes = $new_boxes;
  }

  function handle_action_change_adbox_settings() {
    if ($member_id = get_option('plugin-wonderful-memberid')) {
      if (isset($_POST['template_tag_id']) && is_array($_POST['template_tag_id'])) {
        if (is_array($this->publisher_info->adboxes)) {
          $new_boxes = array();
          foreach ($this->publisher_info->adboxes as $box) {
            if (isset($_POST['template_tag_id'][$box->adboxid])) {
              $tag = $_POST['template_tag_id'][$box->adboxid];
              $prior_value = $box->template_tag_id;

              $tag = $this->adboxes_client->trim_field('template_tag_id', $tag);

              $this->adboxes_client->set_template_tag($box->adboxid, $tag);
              $box->template_tag_id = $tag;

              if (!empty($tag) && ($prior_value != $tag)) {
                $this->messages[] = sprintf(__('Template tag identifier for ad <strong>%1$s</strong> set to <strong>%2$s</strong>.', 'plugin-wonderful'), $box->adboxid, $tag);
              } else {
                if (!empty($prior_value) && empty($tag)) {
                  $this->messages[] = sprintf(__('Template tag identifier for ad <strong>%s</strong> removed.', 'plugin-wonderful'), $box->adboxid);
                }
              }
            }
            $new_boxes[] = $box;
          }
          $this->publisher_info->adboxes = $new_boxes;
        }
      }

      if (is_array($this->publisher_info->adboxes)) {
        $new_boxes = array();
        foreach ($this->publisher_info->adboxes as $box) {
          if (isset($_POST['in_rss_feed'][$box->adboxid])) {
            $this->adboxes_client->set_rss_feed_usage($box->adboxid, true);
            if ($box->in_rss_feed == 0) {
              $this->messages[] = sprintf(__('RSS feed usage for ad <strong>%1$s</strong> enabled.', 'plugin-wonderful'), $box->adboxid);
            }
            $box->in_rss_feed = "1";
          } else {
            $this->adboxes_client->set_rss_feed_usage($box->adboxid, false);
            if ($box->in_rss_feed == 1) {
              $this->messages[] = sprintf(__('RSS feed usage for ad <strong>%1$s</strong> disabled.', 'plugin-wonderful'), $box->adboxid);
            }
            $box->in_rss_feed = "0";
          }
          $new_boxes[] = $box;
        }
        $this->publisher_info->adboxes = $new_boxes;
      }
    }

    if (count($this->messages) == 0) {
      $this->messages[] = __("No changes to adboxes were made.", 'plugin-wonderful');
    }
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
add_filter('the_excerpt_rss', array($plugin_wonderful, 'insert_rss_feed_ads'));

register_activation_hook(__FILE__, array($plugin_wonderful, 'handle_activation'));

function the_project_wonderful_ad($adboxid) {
  global $plugin_wonderful;

  $plugin_wonderful->render_widget(array(), $adboxid);
}

?>
