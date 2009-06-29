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
	
  function testRenderWidget() {
    global $plugin_wonderful;
    $plugin_wonderful = $this->getMock('PluginWonderful', array('_render_adbox'));
    $plugin_wonderful->expects($this->once())->method('_render_adbox');
    
    $this->w->widget(array(), array());
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
    $this->w->form(array('adboxid' => '123', 'center' => 1));
    $source = ob_get_clean();
    
    $this->assertTrue(($xml = _to_xml($source)) !== false);
    
    foreach (array(
      '//input[@type="radio" and @name="' . $this->w->get_field_name('adboxid') . '" and @value="123" and @checked="checked"]' => true,
      '//input[@type="radio" and @name="' . $this->w->get_field_name('adboxid') . '" and @value="234" and not(@checked="checked")]' => true,
      '//input[@type="radio" and @name="' . $this->w->get_field_name('adboxid') . '" and @value="345" and not(@checked="checked")]' => true,
      '//input[@type="checkbox" and @name="' . $this->w->get_field_name('center') . '" and @value="1" and @checked="checked"]' => true
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }
  }
  
  function testUpdateWidget() {
    $this->assertEquals(array('adboxid' => 5, 'center' => 0), $this->w->update(array('adboxid' => 5), array('adboxid' => 4, 'center' => 1)));
  }
}

?>