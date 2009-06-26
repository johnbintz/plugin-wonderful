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
	
	function testWidget() {
	
	}
}

?>