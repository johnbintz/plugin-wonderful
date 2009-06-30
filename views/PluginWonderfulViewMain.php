<?php

class PluginWonderfulViewMain {
  function _create_nonce() {
    $this->_pw_nonce = wp_create_nonce('plugin-wonderful');
  }
  
  function _partial_path($name) {
    return dirname(__FILE__) . '/' . __CLASS__ . '/' . $name . '.php';
  }
  
  function _render_memberid_settings() {
    include($this->_partial_path('memberid-settings'));
  }
}

?>