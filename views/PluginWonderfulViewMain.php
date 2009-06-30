<?php

class PluginWonderfulViewMain {
  function render() {
    $this->_create_nonce();
    $this->_get_first_adboxid();
    $this->_render_memberid_settings();
    $this->_render_adbox_information();
    $this->_render_instructions();
  }

  function _create_nonce() {
    $this->_pw_nonce = wp_create_nonce('plugin-wonderful');
  }
  
  function _get_first_adboxid() {
    global $plugin_wonderful;
    
    $this->first_adboxid = null;
    if ($plugin_wonderful->publisher_info !== false) {
      foreach ($plugin_wonderful->publisher_info->adboxes as $adbox) {
        if (empty($this->first_adboxid)) {
          if (!empty($adbox->template_tag_id)) {
            $this->first_adboxid =  "'" . $adbox->template_tag_id . "'";
          } else {
            $this->first_adboxid = $adbox->adboxid;
          }
          break;
        }
      }
    }
  }
  
  function _partial_path($name) {
    return dirname(__FILE__) . '/' . __CLASS__ . '/' . $name . '.inc';
  }
  
  function _render_memberid_settings() {
    include($this->_partial_path('memberid-settings'));
  }
  
  function _render_adbox_information() {
    global $plugin_wonderful;
    include($this->_partial_path('adbox-information'));
  }

  function _render_instructions() {
    global $plugin_wonderful;
    include($this->_partial_path('instructions'));
  }
}

?>