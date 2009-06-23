<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../classes/PluginWonderful.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');

class PluginWonderfulTest extends PHPUnit_Framework_TestCase {
  function setUp() {
	  $this->pw = new PluginWonderful();
		$_POST = array();
	}
	
  function testSaveWidgetsIsCalled() {
		_set_valid_nonce("plugin-wonderful", "12345");
		$_POST['pw']['_nonce'] = "12345";
	
	  $pw = $this->getMock('PluginWonderful', array('handle_action_save_widgets'));
		$pw->expects($this->once())->method("handle_action_save_widgets");
		$pw->handle_action();
	}
	
	function testRenderWidgetControl() {
		_set_valid_nonce("plugin-wonderful", "12345");

		$this->pw->publisher_info->adboxes = array(
		  (object)array('adboxid' => '123',
			              'center_widget' => 0)
		);
		
		ob_start();
		$this->pw->render_widget_control('123');
		$source = ob_get_clean();
		
		$this->assertTrue(($xml = _to_xml($source)) !== false);
		
		foreach (array(
		  '//input[@name="pw[_nonce]" and @value="12345"]' => true
		) as $xpath => $value) {
		  $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
		}
	}
	
	function testHandleActivation() {
		$pw = $this->getMock('PluginWonderful', array('init'));
		$pw->adboxes_client = $this->getMock('PWAdboxesClient', array('initialize'));
		
		$pw->expects($this->once())->method("init");
		$pw->adboxes_client->expects($this->once())->method('initialize');
		
		$pw->handle_activation();
	}
}

?>