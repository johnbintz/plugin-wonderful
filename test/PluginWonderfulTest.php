<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../classes/PluginWonderful.php');
require_once(dirname(__FILE__) . '/../classes/PublisherInfo.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');

define("PLUGIN_WONDERFUL_DATABASE_VERSION", 5);
define('PLUGIN_WONDERFUL_UPDATE_TIME', 60 * 60 * 12); // every 12 hours

class PluginWonderfulTest extends PHPUnit_Framework_TestCase {
  function setUp() {
	  $this->pw = new PluginWonderful();
		$_POST = array();
		_reset_wp();
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
	
	function providerTestGetPubliserInfo() {
	  return array(
		  array(
				array(
					'plugin-wonderful-memberid' => "",
			  ),
				false,
				false,
				false
		  ),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => time()
				),
				false,
				false,
				"~*test*~"
			),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => 0
				),
				false,
				false,
				"~*test*~"
			),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => 0
				),
				true,
				false,
				"~*test-xml*~"
			),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => 0
				),
				true,
				true,
				"~*test-xml*~"
			)
		);
	}
	
	/**
	 * @dataProvider providerTestGetPubliserInfo
	 */
	function testGetPublisherInfo($options, $retrieve_url_return, $parse_success, $expected_result) {
		foreach ($options as $key => $value) { update_option($key, $value); }
		$pw = $this->getMock('PluginWonderful', array('_retrieve_url', '_get_new_publisher_info_object'));
		$pw->adboxes_client = $this->getMock('PWAdboxesClient', array('get_ads', 'post_ads'));
		
		$test_publisher_info = $this->getMock('PublisherInfo');
		$test_xml_publisher_info = $this->getMock('PublisherInfo', array('parse'));
		
		if (is_numeric($options['plugin-wonderful-memberid'])) {
			$pw->adboxes_client->expects($this->once())->method('get_ads')->will($this->returnValue($test_publisher_info));
			
			if (($options['plugin-wonderful-last-update'] + PLUGIN_WONDERFUL_UPDATE_TIME) < time()) {
				$pw->expects($this->once())->method('_retrieve_url')->will($this->returnValue($retrieve_url_return));
				
				if ($retrieve_url_return) {
				  $pw->expects($this->once())->method('_get_new_publisher_info_object')->will($this->returnValue($test_xml_publisher_info));
					
					$test_xml_publisher_info->expects($this->once())->method('parse')->will($this->returnValue($parse_success));
					
					if ($parse_success) {
					  $pw->adboxes_client->expects($this->once())->method('post_ads');
					} else {
					  $pw->adboxes_client->expects($this->never())->method('post_ads');
					}
				} else {
				  $pw->expects($this->never())->method('_get_new_publisher_info_object');
				}
			} else {
				$pw->expects($this->never())->method('_retrieve_url');
			}
	  }
		
		if ($expected_result == "~*test*~") { $expected_result = $test_publisher_info; }
		if ($expected_result == "~*test-xml*~") { $expected_result = $test_xml_publisher_info; }
		
		$this->assertEquals($expected_result, $pw->_get_publisher_info());
	}
	
	function providerTestUpdateDatabaseVersion() {
	  return array(
		  array(false, true, false),
			array(false, true, true),
			array(PLUGIN_WONDERFUL_DATABASE_VERSION - 1, true, false),
			array(PLUGIN_WONDERFUL_DATABASE_VERSION - 1, true, true),
			array(PLUGIN_WONDERFUL_DATABASE_VERSION, false, false),
			array(PLUGIN_WONDERFUL_DATABASE_VERSION, false, true)
		);
	}
	
	/**
	 * @dataProvider providerTestUpdateDatabaseVersion
	 */
	function testUpdateDatabaseVersion($option, $will_initialize, $initialize_results) {
	  update_option('plugin-wonderful-database-version', $option);
		
		$this->pw->adboxes_client = $this->getMock('PWAdboxesClient', array('initialize'));
		if ($will_initialize) {
			$this->pw->adboxes_client->expects($this->once())->method('initialize')->will($this->returnValue($initialize_results));
	  } else {
			$this->pw->adboxes_client->expects($this->never())->method('initialize');
		}
		
		$this->pw->_update_database_version();
		
		if ($will_initialize) {
			if ($initialize_results) {
				$this->assertEquals(PLUGIN_WONDERFUL_DATABASE_VERSION, get_option('plugin-wonderful-database-version'));
			} else {
				$this->assertEquals(1, count($this->pw->messages));
			}
		}
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
	  $test_ad = (object)array(
		  'adboxid' => '123',
			'template_tag_id' => 'abc',
			'standardcode' => 'standard',
			'advancedcode' => 'advanced',
			'center_widget' => $center_widget
		);
		
		if ($has_publisher_info) {
		  $this->pw->publisher_info = (object)array(
			  'adboxes' => array($test_ad)
			);
			
			update_option("plugin-wonderful-use-standardcode", $use_standardcode);
		} else {
		  $this->pw->publisher_info = false;
		}
		
		ob_start();
		$this->pw->render_widget($requested_adboxid);
		$this->assertEquals($expected_result, ob_get_clean());
	}
}

?>