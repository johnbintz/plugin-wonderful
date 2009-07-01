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
    $plugin_wonderful = $this->getMock('PluginWonderful', array('_render_adbox_admin'));
    $plugin_wonderful->expects($this->once())->method('_render_adbox_admin');
    
    $this->w->form(array());
  }
  
  function testUpdateWidget() {
    $this->assertEquals(array('adboxid' => 5, 'center' => 0), $this->w->update(array('adboxid' => 5), array('adboxid' => 4, 'center' => 1)));
  }
}

?>