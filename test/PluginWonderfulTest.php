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
			  )
		  ),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => time()
				)
			),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => 0
				)
			),
			array(
				array(
					'plugin-wonderful-memberid' => 1,
					'plugin-wonderful-last-update' => 0
				)
			),
		);
	}
	
	/**
	 * @dataProvider providerTestGetPubliserInfo
	 */
	function testGetPublisherInfo($options) {
		foreach ($options as $key => $value) { update_option($key, $value); }
		$pw = $this->getMock('PluginWonderful', array('_download_project_wonderful_data'));
		$pw->adboxes_client = $this->getMock('PWAdboxesClient', array('get_ads', 'post_ads'));
		
		if (is_numeric($options['plugin-wonderful-memberid'])) {
			$pw->adboxes_client->expects($this->once())->method('get_ads')->will($this->returnValue($test_publisher_info));
			
			if (($options['plugin-wonderful-last-update'] + PLUGIN_WONDERFUL_UPDATE_TIME) < time()) {
				$pw->expects($this->once())->method('_download_project_wonderful_data');
			} else {
				$pw->expects($this->never())->method('_download_project_wonderful_data');
			}
	  }
    
    $pw->_get_publisher_info();
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
   
    $plugin_wonderful = $this->getMock('PluginWonderful', array('_render_adbox'));
    $plugin_wonderful->expects($this->once())->method('_render_adbox');
    the_project_wonderful_ad('123');
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
  
  function providerTestShowView() {
    return array(
      array(null, false),
      array((object)array(), false),
      array($this->getMock('Test', array('render')), true)
    );
  }
  
  /**
   * @dataProvider providerTestShowView
   */
  function testShowView($class, $is_success) {
    global $wp_test_expectations;
    $wp_test_expectations['plugin_data'][realpath(dirname(__FILE__) . '/../classes/PluginWonderful.php')] = array(
      'Title' => '**title**',
      'Version' => '**version**',
      'Author' => '**author**'
    );
  
    ob_start();
    $this->pw->show_view($class);
    $source = ob_get_clean();

    if ($is_success) {
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
     $_POST['_pw_action'] = 'test';
     
     if ($method_exists) {
       $pw->expects($this->once())->method('handle_action_test');
     } else {
       $pw->expects($this->never())->method('handle_action_invalid');     
     }
     
     $pw->handle_action();
  }
  
  function providerTestHandleActionChangeAdboxSettings() {
    return array(
      array(false),
      array(true),
    );
  }
  
  /**
   * @dataProvider providerTestHandleActionChangeAdboxSettings
   */
  function testHandleActionChangeAdboxSettings($member_id_valid) {
    $pw = new PluginWonderful();
    
    if ($member_id_valid) {
      update_option('plugin-wonderful-memberid', '1');    
    }
    
    if ($member_id_valid) {
      foreach (array(false, true) as $had_template_tag_id) {
        foreach (array("null", "no", "yes", "remove") as $new_template_tag_id) {
          foreach (array(false, true) as $was_in_rss_feed) {
            foreach (array("null", "no", "yes") as $now_in_rss_feed) {
              $pw->publisher_info = (object)array(
                'adboxes' => array(
                  (object)array(
                    'adboxid' => '123',
                    'template_tag_id' => ($had_template_tag_id ? "test" : ""),
                    'in_rss_feed' => ($was_in_rss_feed ? "1" : "0")
                  )
                )
              );
              
              $pw->adboxes_client = $this->getMock('PWAdboxesClient', array('trim_field', 'set_template_tag', 'set_rss_feed_usage'));

              $_POST['template_tag_id'] = array();

              switch ($new_template_tag_id) {
                case "no": $_POST['template_tag_id']['123'] = "test"; break;
                case "yes": $_POST['template_tag_id']['123'] = "test2"; break;
                case "remove": $_POST['template_tag_id']['123'] = ""; break;
              }
              
              if ($new_template_tag_id !== "null") {
                $pw->adboxes_client->expects($this->once())->method('trim_field')->with('template_tag_id', $_POST['template_tag_id']['123'])->will($this->returnValue($_POST['template_tag_id']['123']));
                $pw->adboxes_client->expects($this->once())->method('set_template_tag')->with('123', $_POST['template_tag_id']['123']);
              }
              
              $_POST['in_rss_feed'] = array();
              
              switch ($now_in_rss_feed) {
                case "no": unset($_POST['in_rss_feed']['123']); break;
                case "yes": $_POST['in_rss_feed']['123'] = "1"; break;
              }
              
              $result = $pw->handle_action_change_adbox_settings();
              
              if (isset($_POST['template_tag_id']['123'])) {
                $this->assertEquals($_POST['template_tag_id']['123'], $pw->publisher_info->adboxes[0]->template_tag_id);
              }

              if (isset($_POST['in_rss_feed']['123'])) {
                $this->assertEquals($_POST['in_rss_feed']['123'], $pw->publisher_info->adboxes[0]->in_rss_feed);
              }

              switch ($new_template_tag_id) {
                case "yes":
                  $this->assertEquals("set", $result['template_tag_id']['123']);
                  break;
                case "remove":
                  if ($had_template_tag_id) {
                    $this->assertEquals("removed", $result['template_tag_id']['123']);
                  }
                  break;
              }

              switch ($now_in_rss_feed) {
                case "no":
                  if ($was_in_rss_feed) {
                    $this->assertEquals("disabled", $result['in_rss_feed']['123']);
                  }
                  break;
                case "yes":
                  if (!$was_in_rss_feed) {                    
                    $this->assertEquals("enabled", $result['in_rss_feed']['123']);
                  }
                  break;
              }
            }
          }
        }
      }
    } else {
      $this->assertTrue(is_null($result));
    }
  }

  function providerTestDownloadProjectWonderfulData() {
    return array(
      array(false, false, "can't read"),
      array(true, false, "can't parse"),
      array(true, true, "downloaded"),
    );
  }
  
  /**
   * @dataProvider providerTestDownloadProjectWonderfulData
   */
  function testDownloadProjectWonderfulData($did_download_data, $did_parse_data, $expected_result) {
    $pw = $this->getMock('PluginWonderful', array('_retrieve_url', '_get_new_publisher_info_object'));
    
    update_option('plugin-wonderful-last-update', 0);
    
    $pw->expects($this->once())->method('_retrieve_url')->will($this->returnValue($did_download_data));
    if ($did_download_data) {      
      $publisher_info = $this->getMock('PublisherInfo');
      $publisher_info->expects($this->once())->method('parse')->will($this->returnValue($did_parse_data));
      
      $pw->expects($this->once())->method('_get_new_publisher_info_object')->will($this->returnValue($publisher_info));
      
      if ($did_parse_data) {
        $pw->adboxes_client = $this->getMock('PWAdboxesClient', array('post_ads'));
        $pw->adboxes_client->expects($this->once())->method('post_ads');
      }
    }
    
    $this->assertEquals($expected_result, $pw->_download_project_wonderful_data('123'));
    
    if ($did_parse_data) {
      $this->assertNotEquals(0, get_option('plugin-wonderful-last-update'));
    }
  }

  function providerTestHandleActionRebuildDatabase() {
    return array(
      array(""), array(1)
    );
  }

  /**
   * @dataProvider providerTestHandleActionRebuildDatabase
   */
  function testHandleActionRebuildDatabase($member_id) {
    $pw = $this->getMock('PluginWonderful', array('_download_project_wonderful_data'));
    $pw->adboxes_client = $this->getMock('PWAdboxesClient', array('destroy', 'initialize'));
    
    update_option('plugin-wonderful-memberid', $member_id);
    if (!empty($member_id)) {
      $pw->expects($this->once())->method("_download_project_wonderful_data")->with($member_id);
    }
    
    $pw->handle_action_rebuild_database();
  }
  
  function providerTestHandleActionChangeMemberID() {
    return array(
      array("", "", false, true),
      array("", "1.5", false, true),
      array("", "a", false, true),
      array("", "1", true, false),
      array("1", "1", false, false)
    );
  }
  
  /**
   * @dataProvider providerTestHandleActionChangeMemberID
   */
  function testHandleActionChangeMemberID($original_member_id, $member_id, $is_downloaded, $member_id_blank) {
    $_POST['memberid'] = $member_id;
    update_option('plugin-wonderful-memberid', $original_member_id);
    
    $pw = $this->getMock('PluginWonderful', array("_download_project_wonderful_data"));
    if ($is_downloaded) {
      $pw->expects($this->once())->method('_download_project_wonderful_data');
    } else {
      $pw->expects($this->never())->method('_download_project_wonderful_data');    
    }
    
    $pw->handle_action_change_memberid();
    
    $result = get_option('plugin-wonderful-memberid');
    $this->assertEquals($member_id_blank, empty($result));
  }
  
  function providerTestRenderAdbox() {
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
   * @dataProvider providerTestRenderAdbox
   */
  function testRenderAdbox($has_publisher_info, $requested_adboxid, $use_standardcode, $center_widget, $expected_result) {
    global $plugin_wonderful;

    $test_ad = (object)array(
      'adboxid' => '123',
      'template_tag_id' => 'abc',
      'standardcode' => 'standard',
      'advancedcode' => 'advanced'
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
    $this->pw->_render_adbox($requested_adboxid, $center_widget);
    $this->assertEquals($expected_result, ob_get_clean());
  }
  
  function testRenderAdboxAdmin() {
    $this->pw->publisher_info->adboxes = array(
      (object)array('adboxid' => '123'),
      (object)array('adboxid' => '234'),
      (object)array('adboxid' => '345'),
    );
    
    ob_start();
    $this->pw->_render_adbox_admin(array('adboxid' => '123', 'center' => 1), array('adboxid' => 'adname', 'center' => 'centername'));
    $source = ob_get_clean();
    
    $this->assertTrue(($xml = _to_xml($source)) !== false);
    
    foreach (array(
      '//input[@type="radio" and @name="adname" and @value="123" and @checked="checked"]' => true,
      '//input[@type="radio" and @name="adname" and @value="234" and not(@checked="checked")]' => true,
      '//input[@type="radio" and @name="adname" and @value="345" and not(@checked="checked")]' => true,
      '//input[@type="checkbox" and @name="centername" and @value="1" and @checked="checked"]' => true
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }  
  }
  
  function providerTestRenderPre28Widget() {
    return array(
      array(false, false),
      array(array('blah' => 'yadda'), false),
      array(array('adboxid' => '1', 'center' => 1), false),
      array(array('adboxid' => '123', 'center' => 1), true)
    );
  }
  
  /**
   * @dataProvider providerTestRenderPre28Widget
   */
  function testRenderPre28Widget($option_value, $success) {
    update_option('plugin-wonderful-pre28-widget-info', $option_value);
  
    $this->pw->publisher_info->adboxes = array(
      (object)array('adboxid' => '123'),
    );
  
    ob_start();
    $this->pw->render_pre28_widget();
    $source = ob_get_clean();
    
    $this->assertEquals($success, !empty($source));
  }
  
  function testRenderPre28WidgetControl() {
    update_option('plugin-wonderful-pre28-widget-info', array('adboxid' => 123, 'center' => 1));
    
    $this->pw->publisher_info->adboxes = array(
      (object)array('adboxid' => '123'),
    );
    
    ob_start();
    $this->pw->render_pre28_widget_control();
    $source = ob_get_clean();

    $this->assertTrue(($xml = _to_xml($source)) !== false);
   
    foreach (array(
      '//input[@name="_pw_nonce"]' => true,
      '//input[@name="pw[adboxid]"]' => true,
      '//input[@name="pw[center]"]' => true,
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }
  }
  
  function providerTestNormalizePre28Option() {
    return array(
      array(
        false,
        array('adboxid' => false, 'center' => 0)
      ),
      array(
        array(),
        array('adboxid' => false, 'center' => 0)
      ),
      array(
        array('adboxid' => 'meow'),
        array('adboxid' => false, 'center' => 0)
      ),
      array(
        array('adboxid' => '123'),
        array('adboxid' => '123', 'center' => 0)
      ),
    );
  }
  
  /**
   * @dataProvider providerTestNormalizePre28Option
   */
  function testNormalizePre28Option($option_value, $expected_result) {
    update_option('plugin-wonderful-pre28-widget-info', $option_value);
    
    $this->assertEquals($expected_result, $this->pw->_normalize_pre28_option());
    $this->assertEquals($expected_result, get_option('plugin-wonderful-pre28-widget-info'));
  }
}

?>
