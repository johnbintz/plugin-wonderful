<?php

require_once('PluginWonderfulWidget.php');

class PluginWonderful {
  var $messages, $adboxes_client, $publisher_info, $member_id;
  var $widget_prefix = "plugin-wonderful";
  
  var $message_types = array(
    'CANT_READ' => "can't read",
    'CANT_PARSE' => "can't parse",
    'DOWNLOADED' => "downloaded"
  );

  function PluginWonderful() {}
  
  /**
   * Wrapper around file_get_contents for testing purposes.
   */
  function _retrieve_url($url) {
    return @file_get_contents($url);
  }
  
  /**
   * Initialize the object if it isn't already.
   */
  function init() {
    if (empty($this->adboxes_client)) {
      $this->messages = array();
      $this->adboxes_client = new PWAdboxesClient();
      
      $this->_get_publisher_info();
      $this->_update_database_version();
      
      if (!empty($_POST)) { $this->handle_action(); }	
    }
  }

  /**
   * Wrapper around PublisherInfo generation for testing.
   */
  function _get_new_publisher_info_object() {
    return new PublisherInfo();
  }

  /**
   * Check to see if the database schema needs to be updated.
   */
  function _update_database_version() {
    $result = get_option('plugin-wonderful-database-version');
    if (empty($result) || ($result < PLUGIN_WONDERFUL_DATABASE_VERSION)) {
      if ($this->adboxes_client->initialize(true)) {
        update_option('plugin-wonderful-database-version', PLUGIN_WONDERFUL_DATABASE_VERSION);
      } else {
        $this->messages[] = "Unable to update database schema!";
      }
    }	
  }

  /**
   * Get publisher info (adboxes) from cache or from Project Wonderful.
   * @return PublisherInfo|boolean The PublisherInfo object with adbox information, or false if there was a problem.
   */
  function _get_publisher_info() {
    $this->publisher_info = false;
    $member_id = get_option('plugin-wonderful-memberid');
    if (is_numeric($member_id)) {
      $member_id = (int)$member_id;
      $this->publisher_info = $this->adboxes_client->get_ads($member_id);

      $last_update = get_option('plugin-wonderful-last-update') ;
      if (!is_numeric($last_update)) { $last_update = 0; }
      $last_update = (int)$last_update;
      
      if (($last_update + PLUGIN_WONDERFUL_UPDATE_TIME) < time()) {
        $this->_download_project_wonderful_data($member_id);
      }
    }	
    
    return $this->publisher_info;
  }

  function insert_rss_feed_ads($content) {
    if (is_feed()) {
      if ($this->publisher_info !== false) {
        foreach ($this->publisher_info->adboxes as $adbox) {
          if ($adbox->in_rss_feed == 1) {
            if (preg_match("#<noscript>(.*)</noscript>#mis", $adbox->advancedcode, $matches) > 0) {
              echo $matches[1];
            }
          }
        }
      }
    }
    return $content;
  }


  /**
   * Inject ads into the body copy of posts.
   * @param string $body The body to search for PW() tags.
   * @return string The modified body.
   */
  function inject_ads_into_body_copy($body) {
    if ($this->publisher_info !== false) {
      if (get_option("plugin-wonderful-enable-body-copy-embedding") == 1) {
        return $this->publisher_info->inject_ads_into_body_copy($body, (get_option("plugin-wonderful-use-standardcode") == 1));
      }
    }
    return $body;
  }

  function set_up_menu() {
    add_options_page('Plugin Wonderful', __("Plugin Wonderful", 'plugin-wonderful'), 5, __FILE__, array($this, "plugin_wonderful_main"));
  }

  function handle_activation() {
    $this->init();
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
    return dirname(__FILE__) . "/../{$source}/{$name}.php";
  }

  function _include($target) { include($target); }
  function _file_exists($target) { return @file_exists($target); }

  function get_view($function_name) {
    $target = $this->_create_target(str_replace('plugin_wonderful_', '', $function_name), "views");
    if ($this->_file_exists($target)) {
      $info = get_plugin_data(realpath(__FILE__));

      echo '<div class="wrap">';
        echo '<div id="icon-edit" class="icon32"><br /></div>';
        echo '<h2>' . __("Plugin Wonderful", 'plugin-wonderful') . '</h2>';

        $this->show_messages();

        $this->_include($target);

        echo '<div style="margin-top: 20px; border-top: solid #E3E3E3 1px; overflow: hidden">';
          echo '<form style="float: right; display: inline" action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick"><input type="hidden" name="hosted_button_id" value="3215507"><input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt=""><img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1"></form>';
          echo sprintf(__('%1$s Version %2$s by %3$s', 'plugin-wonderful'), $info['Title'], $info['Version'], $info['Author']) . ' | ';
          echo __('<a href="http://www.projectwonderful.com/login.php">Manage your Project Wonderful publisher account</a>', 'plugin-wonderful');
          echo '<br style="clear: both" />';
        echo '</div>';
      echo '</div>';
    } else {
      echo __("View not found: ", 'plugin-wonderful') . str_replace('plugin_wonderful_', '', $function_name);
    }
  }

  function handle_action() {
    if (!empty($_POST['_pw_nonce'])) {
      if (wp_verify_nonce($_POST['_pw_nonce'], 'plugin-wonderful')) {
        $action = "handle_action_" . str_replace("-", "_", preg_replace('#[^a-z\-]#', '', strtolower($_POST['action'])));
        if (method_exists($this, $action)) { call_user_func(array($this, $action)); }
      }
    }
  }

  function handle_action_change_adbox_settings() {
    $member_id = get_option('plugin-wonderful-memberid');
    if (is_numeric($member_id)) {
      $changes = array(
        'template_tag_id' => array(),
        'in_rss_feed' => array()
      );
    
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
              $changes['template_tag_id'][$box->adboxid] = "set";
            } else {
              if (!empty($prior_value) && empty($tag)) {
                $this->messages[] = sprintf(__('Template tag identifier for ad <strong>%s</strong> removed.', 'plugin-wonderful'), $box->adboxid);
                $changes['template_tag_id'][$box->adboxid] = "removed";
              }
            }
          }
          
          if (!empty($_POST['in_rss_feed'][$box->adboxid])) {
            $this->adboxes_client->set_rss_feed_usage($box->adboxid, true);
            if ($box->in_rss_feed == 0) {
              $this->messages[] = sprintf(__('RSS feed usage for ad <strong>%1$s</strong> enabled.', 'plugin-wonderful'), $box->adboxid);
              $changes['in_rss_feed'][$box->adboxid] = "enabled";
            }
            $box->in_rss_feed = "1";
          } else {
            $this->adboxes_client->set_rss_feed_usage($box->adboxid, false);
            if ($box->in_rss_feed == 1) {
              $this->messages[] = sprintf(__('RSS feed usage for ad <strong>%1$s</strong> disabled.', 'plugin-wonderful'), $box->adboxid);
              $changes['in_rss_feed'][$box->adboxid] = "disabled";
            }
            $box->in_rss_feed = "0";
          }
          
          $new_boxes[] = $box;
        }
        
        $this->publisher_info->adboxes = $new_boxes;
      }
    } else {
      return null;    
    }

    if (count($this->messages) == 0) {
      $this->messages[] = __("No changes to adboxes were made.", 'plugin-wonderful');
    }
    return $changes;
  }

  function _download_project_wonderful_data($member_id) {
    if (($result = $this->_retrieve_url(sprintf(PLUGIN_WONDERFUL_XML_URL, $member_id))) !== false) {
      $this->publisher_info = $this->_get_new_publisher_info_object();
      if ($this->publisher_info->parse($result)) {
        $this->adboxes_client->post_ads($this->publisher_info);
        return $this->message_types['DOWNLOADED'];
      } else {
        $this->publisher_info = false;
        return $this->message_types['CANT_PARSE'];
      }
    } else {
      $this->publisher_info = false;
      return $this->message_types['CANT_READ'];
    }
  }

  function handle_action_rebuild_database() {
    $this->adboxes_client->destroy();
    $this->adboxes_client->initialize();

    $this->messages[] = __("Adbox database destroyed and rebuilt.", 'plugin-wonderful');

    $result = get_option('plugin-wonderful-memberid');
    if (!empty($result)) {
      switch ($this->_download_project_wonderful_data($result)) {
        case $this->message_types['DOWNLOADED']:
          $this->messages[] = __('Adbox information redownloaded.', 'plugin-wonderful');
          break;
        case $this->message_types['CANT_PARSE']:
          $this->messages[] = __("Unable to parse publisher data from Project Wonderful.", 'plugin-wonderful');
          break;
        case $this->message_types['CANT_READ']:
          $this->messages[] = __("Unable to read publisher data from Project Wonderful.", 'plugin-wonderful');
          break;
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

    foreach (array('use-standardcode', 'enable-body-copy-embedding') as $field) {
      update_option("plugin-wonderful-${field}", isset($_POST[$field]) ? "1" : "0");
    }
  }
}

function the_project_wonderful_ad($adboxid) {
  $w = new PluginWonderfulWidget();
  $w->widget(array(), array('adboxid' => $adboxid));
}

?>
