<?php

if (class_exists('WP_Widget')) {
  class PluginWonderfulWidget extends WP_Widget {
    function PluginWonderfulWidget() {
      $widget_options = array(
        'classname' => 'plugin-wonderful',
        'description' => __('A widget for adding your Project Wonderful advertisements', 'plugin-wonderful')
      );
      
      $control_options = array(
        'id_base' => 'plugin-wonderful'
      );
      
      $this->WP_Widget('plugin-wonderful', __('Plugin Wonderful', 'plugin-wonderful'), $widget_options, $control_options);
    }
    
    function widget($args, $instance) {
      global $plugin_wonderful;
      $plugin_wonderful->_render_adbox($instance['adboxid'], $instance['center']);
    }
    
    function form($instance) {
      global $plugin_wonderful;
      $plugin_wonderful->_render_adbox_admin(
        $instance,
        array(
          'adboxid' => $this->get_field_name('adboxid'),
          'center' => $this->get_field_name('center')
        )
      );
    }
    
    function update($new_instance, $old_instance) {
      $instance = $new_instance;
      if (!isset($instance['center'])) { $instance['center'] = 0; }
      return $instance;
    }
  }
}

?>