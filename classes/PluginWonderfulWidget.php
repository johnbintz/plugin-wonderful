<?php

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
		
		$plugin_wonderful->render_widget($instance['adboxid']);
	}
}

?>