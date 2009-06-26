<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');
require_once(dirname(__FILE__) . '/../classes/PluginWonderfulWidget.php');

class PluginWonderfulWidgetTest extends PHPUnit_Framework_TestCase {
  function setUp() {
	  _reset_wp();
    $this->w = new PluginWonderfulWidget();
  }
	
	function testInitialize() {
	  global $wp_test_expectations;
		
		$this->w = new PluginWonderfulWidget();
		$this->assertEquals("Plugin Wonderful", $wp_test_expectations['wp_widgets']['plugin-wonderful']['name']);
	}
	  
  function providerTestRenderWidget() {
    return array(
      array(false, null, null, null, ""),
      array(true, null, null, null, ""),
      array(true, "123", 0, null, "advanced"),
      array(true, "123", 1, null, "standard"),
      array(true, "abc", 1, null, "standard"),
      array(true, "abc", 1, 1, "<center>standard</center>")
    );
  }
  
  /**
   * @dataProvider providerTestRenderWidget
   */
  function testRenderWidget($has_publisher_info, $requested_adboxid, $use_standardcode, $center_widget, $expected_result) {
    global $plugin_wonderful;
    $plugin_wonderful = $this->getMock('PluginWonderful');
    
    $test_ad = (object)array(
      'adboxid' => '123',
      'template_tag_id' => 'abc',
      'standardcode' => 'standard',
      'advancedcode' => 'advanced'
    );
    
    if ($has_publisher_info) {
      $plugin_wonderful->publisher_info = (object)array(
        'adboxes' => array($test_ad)
      );
      
      update_option("plugin-wonderful-use-standardcode", $use_standardcode);
    } else {
      $plugin_wonderful->publisher_info = false;
    }
    
    ob_start();
    $this->w->widget(array(), array('adboxid' => $requested_adboxid, 'center' => $center_widget));
    $this->assertEquals($expected_result, ob_get_clean());
  }
  
  
  function testRenderWidgetControl() {
    global $plugin_wonderful;
    $plugin_wonderful = $this->getMock('PluginWonderful');
    
    $plugin_wonderful->publisher_info->adboxes = array(
      (object)array('adboxid' => '123'),
      (object)array('adboxid' => '234'),
      (object)array('adboxid' => '345'),
    );
    
    ob_start();
    $this->w->form(array('adboxid' => '123', 'center' => 0));
    $source = ob_get_clean();
    
    $this->assertTrue(($xml = _to_xml($source)) !== false);
    
    foreach (array(
      '//input[@type="radio" and @name="' . $this->w->get_field_name('adboxid') . '" and @value="123" and @checked="checked"]' => true,
      '//input[@type="radio" and @name="' . $this->w->get_field_name('adboxid') . '" and @value="234" and not(@checked="checked")]' => true,
      '//input[@type="radio" and @name="' . $this->w->get_field_name('adboxid') . '" and @value="345" and not(@checked="checked")]' => true,
      '//input[@type="checkbox" and @name="' . $this->w->get_field_name('center') . '" and @value="1" and not(@checked="checked")]' => true
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }
  }  
}

?>