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

    if ($plugin_wonderful->publisher_info !== false) {
      foreach ($plugin_wonderful->publisher_info->adboxes as $adbox) {
        if (($adbox->adboxid == $instance['adboxid']) || ($adbox->template_tag_id == $instance['adboxid'])) {
          if (get_option("plugin-wonderful-use-standardcode") == 1) {
            $output = $adbox->standardcode;
          } else {
            $output = $adbox->advancedcode;
          }
          if ($instance['center'] == 1) {
            $output = "<center>{$output}</center>";
          }
          echo $output;
          break;
        }
      }
    }
	}
  
  function form($instance) {
    global $plugin_wonderful;
    
    if ($plugin_wonderful->publisher_info !== false) {
      echo '<p>';
        echo 'Select an adbox:';
        foreach ($plugin_wonderful->publisher_info->adboxes as $box) {
          echo '<label>';
            echo '<input type="radio" name="'
                 . $this->get_field_name('adboxid')
                 . '" value="'
                 . $box->adboxid
                 . '" '
                 . (($instance['adboxid'] == $box->adboxid) ? 'checked="checked"' : "")
                 . ' />';
            echo $box->adtype . " " . $box->dimensions . " (" . $box->adboxid . ")";
          echo "</label>";
        }
      echo '</p>';
      
      echo '<p>';
        echo '<label>';
          echo '<input type="checkbox" value="1" name="' . $this->get_field_name('center') . '" ' . (($instance['center'] == 1) ? 'checked="checked"' : "") . ' /> ';
          echo 'Wrap ad in &lt;center&gt; tags';
        echo '</label>';
      echo '</p>';
    }
  }
  
  function update($new_instance, $old_instance) {
    $instance = $new_instance;
    if (!isset($instance['center'])) { $instance['center'] = 0; }
    return $instance;
  }
}

?>