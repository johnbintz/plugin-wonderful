<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');
require_once(dirname(__FILE__) . '/../classes/PluginWonderful.php');
require_once(dirname(__FILE__) . '/../classes/PublisherInfo.php');

define("PLUGIN_WONDERFUL_DATABASE_VERSION", 5);
define('PLUGIN_WONDERFUL_UPDATE_TIME', 60 * 60 * 12); // every 12 hours

class PluginWonderfulTest extends PHPUnit_Framework_TestCase {
  function setUp() {
	  $this->pw = new PluginWonderful();
		$_POST = array();
		_reset_wp();
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
  
  function testTemplateTag() {
    global $plugin_wonderful;
    
    $plugin_wonderful = $this->getMock('PluginWonderful');
  
    $plugin_wonderful->publisher_info = (object)array(
      'adboxes' => array(
        (object)array('adboxid' => '123', 'advancedcode' => "test", 'standardcode' => "not-test")
      )
    );
    
    ob_start();
    the_project_wonderful_ad('123');
    $this->assertEquals("test", ob_get_clean());
  }

  function providerInsertAdsIntoRSS() {
    return array(
      array(false, false, 0),
      array(true, false, 0),
      array(true, true, 0),
      array(true, true, 1)
    );
  }

  /**
   * @dataProvider providerInsertAdsIntoRSS
   */
  function testInsertAdsIntoRSS($is_feed, $publisher_info, $in_rss_feed) {
    _set_current_option('is_feed', $is_feed);
    
    if ($is_feed) {
      if ($publisher_info) {
        $this->pw->publisher_info = (object)array(
          'adboxes' => array(
            (object)array('advancedcode' => "<noscript>test</noscript>", 'in_rss_feed' => $in_rss_feed)
          )
        );
      } else {
        $this->pw->publisher_info = false;
      }
    }
    
    ob_start();
    $this->pw->insert_rss_feed_ads("body");
    $source = ob_get_clean();
    
    $this->assertEquals($is_feed && $publisher_info && ($in_rss_feed == 1), !empty($source));
  }
  
  function providerTestInjectAdsIntoBodyCopy() {
    return array(
      array(false, null),
      array(true, 0),
      array(true, 1),
    );
  }
  
  /**
   * @dataProvider providerTestInjectAdsIntoBodyCopy
   */
  function testInjectAdsIntoBodyCopy($has_publisher_info, $enable_embedding) {
    $expected_body = "body";
    
    if ($has_publisher_info) {
      $this->pw->publisher_info = $this->getMock('PublisherInfo', array('inject_ads_into_body_copy'));
      update_option("plugin-wonderful-enable-body-copy-embedding", $enable_embedding);
      
      if ($enable_embedding == 1) {
        $expected_body = "called";
        $this->pw->publisher_info->expects($this->once())->method('inject_ads_into_body_copy')->will($this->returnValue($expected_body));
	    } else {
	      $this->pw->publisher_info->expects($this->never())->method('inject_ads_into_body_copy');
	    }
    } else {
      $this->pw->publisher_info = false;
    }
    
    $this->assertEquals($expected_body, $this->pw->inject_ads_into_body_copy("body"));
  }
  
  function providerTestGetView() {
    return array(
      array("**bad**", false),
      array("**good**", true),
    );
  }
  
  /**
   * @dataProvider providerTestGetView
   */
  function testGetView($function_extension, $file_exists) {
    global $wp_test_expectations;
    $wp_test_expectations['plugin_data'][realpath(dirname(__FILE__) . '/../classes/PluginWonderful.php')] = array(
      'Title' => '**title**',
      'Version' => '**version**',
      'Author' => '**author**'
    );
  
    $pw = $this->getMock('PluginWonderful', array('_create_target', '_include', '_file_exists'));

    $pw->expects($this->once())->method("_file_exists")->will($this->returnValue($file_exists));

    ob_start();
    $pw->get_view("plugin_wonderful_" . $function_extension);
    $source = ob_get_clean();
    
    $this->assertEquals($file_exists, strpos($source, $function_extension) === false);
    
    if ($file_exists) {
      foreach (array("title", "version", "author") as $name) {
        $this->assertTrue(strpos($source, "**${name}**") !== false);
      }
    }
  }
  
  function providerTestHandleAction() {
    return array(
      array(false, false, false),
      array(true, false, false),
      array(true, true, true)
    );
  }
  
  /**
   * @dataProvider providerTestHandleAction
   */
  function testHandleAction($has_nonce, $has_verify_nonce, $method_exists) {
    if ($has_nonce) { $_POST['_pw_nonce'] = "12345"; }
     _set_valid_nonce('plugin-wonderful', $has_verify_nonce ? '12345' : '54321');
          
     $pw = $this->getMock('PluginWonderful', $method_exists ? array('handle_action_test') : array('handle_action_invalid'));
     $_POST['action'] = 'test';
     
     if ($method_exists) {
       $pw->expects($this->once())->method('handle_action_test');
     } else {
       $pw->expects($this->never())->method('handle_action_invalid');     
     }
     
     $pw->handle_action();
  }
  
  function testHandleActionSaveWidgets() {
    $this->markTestIncomplete();
  }
  
  function testHandleActionChangeAdboxSettings() {
    $this->markTestIncomplete();
  }
  
  function testHandleActionRebuildDatabase() {
    $this->markTestIncomplete();
  }
  
  function testHandleActionChangeMemberID() {
    $this->markTestIncomplete();
  }
}

?>
