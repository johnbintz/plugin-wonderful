<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../views/PluginWonderfulViewMain.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');

class ViewMainTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    _reset_wp();
    $_GET = array();
    _set_current_option('is_admin', true);
    $this->m = new PluginWonderfulViewMain();
  }

  function testRender() {
    $m = $this->getMock('PluginWonderfulViewMain', array('_render_memberid_settings', '_render_adbox_information', '_render_instructions', '_create_nonce', '_get_first_adboxid'));
    
    $m->expects($this->once())->method('_render_memberid_settings');
    $m->expects($this->once())->method('_render_adbox_information');
    $m->expects($this->once())->method('_render_instructions');
    $m->expects($this->once())->method('_create_nonce');
    $m->expects($this->once())->method('_get_first_adboxid');
    
    $m->render();
  }

  function testCreateNonce() {
    $this->m->_create_nonce();
    $result = _get_nonce('plugin-wonderful');
    $this->assertTrue(!empty($result));
    $this->assertEquals($result, $this->m->_pw_nonce);
  }

  function providerTestRenderMemberIDSettings() {
    return array(
      array(
        array(
          'plugin-wonderful-memberid' => "123",
          'plugin-wonderful-use-standardcode' => 0,
          'plugin-wonderful-enable-body-copy-embedding' => 0
        )
      ),
      array(
        array(
          'plugin-wonderful-memberid' => "123",
          'plugin-wonderful-use-standardcode' => 1,
          'plugin-wonderful-enable-body-copy-embedding' => 1
        )
      ),
    );
  }

  function providerTestGetFirstAdboxID() {
    return array(
      array(false, null),
      array(
        (object)array('template_tag_id' => 'meow'),
        "'meow'"
      ),
      array(
        (object)array('adboxid' => '123'),
        "123"
      )
    );
  }

  /**
   * @dataProvider providerTestGetFirstAdboxID
   */
  function testGetFirstAdboxID($publisher_info, $expected_result) {
    global $plugin_wonderful;
    
    $plugin_wonderful = (object)array(
      'publisher_info' => false
    );
  
    if (is_object($publisher_info)) {    
      $plugin_wonderful->publisher_info = (object)array(
        'adboxes' => array($publisher_info)
      );
    }
    
    $this->m->_get_first_adboxid();
    $this->assertSame($expected_result, $this->m->first_adboxid);
  }

  /**
   * @dataProvider providerTestRenderMemberIDSettings
   */
  function testRenderMemberIDSettings($options) {
    foreach ($options as $key => $value) {
      update_option($key, $value);
    }
    
    $this->m->_pw_nonce = "345";
    
    ob_start();
    $this->m->_render_memberid_settings();
    $source = ob_get_clean();
    
    $this->assertTrue(!empty($source));
    $this->assertTrue(($xml = _to_xml($source, true)) !== false);
    
    foreach (array(
      '//input[@id="memberid" and @value="123"]' => true,
      '//input[@name="_pw_nonce" and @value="345"]' => true,
      '//input[@name="use-standardcode" and ' . (($options['plugin-wonderful-use-standardcode'] == 1) ? '@checked' : 'not(@checked)') . ']' => true,
      '//input[@name="enable-body-copy-embedding" and ' . (($options['plugin-wonderful-enable-body-copy-embedding'] == 1) ? '@checked' : 'not(@checked)') . ']' => true,
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }
  }
  
  function testRenderAdboxInformation() {
    global $plugin_wonderful;
    
    $plugin_wonderful = (object)array(
      'publisher_info' => false
    );
  
    $this->m->_pw_nonce = "345";
    
    foreach (array(false, true) as $has_publisher_info) {
      if ($has_publisher_info) {
        $plugin_wonderful->publisher_info = (object)array(
          'adboxes' => array(
            (object)array(
              'url' => 'url',
              'sitename' => 'sitename',
              'description' => 'this is a very long description that is over seventy characters in length and should get an ellipsis',
              'adtype' => 'square',
              'dimensions' => '1x2',
              'adboxid' => '12345',
              'template_tag_id' => 'tag',
              'in_rss_feed' => 1
            )
          )
        );
      }
    }
    
    ob_start();
    $this->m->_render_adbox_information();
    $source = ob_get_clean();

    if ($has_publisher_info) {
      $this->assertTrue(!empty($source));
      $this->assertTrue(($xml = _to_xml($source, true)) !== false);
      
      foreach (array(
        '//input[@name="_pw_nonce" and @value="345"]' => true,
        '//table[1]/tr[2]/td[1]/a[@href="url" and contains(@title, "url")]' => 'sitename',
        '//table[1]/tr[2]/td[2 and contains(text(), "...")]' => true,
        '//table[1]/tr[2]/td[3]' => 'square - 1x2',
        '//table[1]/tr[2]/td[4]/input[@name="template_tag_id[12345]" and @value="tag"]' => true,
        '//table[1]/tr[2]/td[5]/input[@name="in_rss_feed[12345]" and @checked]' => true,
        '//table[1]/tr[2]/td[6]/tt' => "the_project_wonderful_ad('tag')"
      ) as $xpath => $value) {
        $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
      }    
    } else {
      $this->assertTrue(empty($source));
    }
  }
  
  function testInstructions() {
    global $plugin_wonderful;
    
    $plugin_wonderful->publisher_info = true;
    
    $this->m->_pw_nonce = "345";
    $this->m->first_adboxid = "123";
    $_GET['allow-destroy'] = 1;
    
    ob_start();
    $this->m->_render_instructions();
    $source = ob_get_clean();

    $this->assertTrue(!empty($source));
    $this->assertTrue(($xml = _to_xml($source, true)) !== false);
    
    foreach (array(
      '//tt[contains(text(), "the_project_wonderful_ad(123)")]' => true,
      '//tt[contains(text(), "PW(123)")]' => true,
      '//tt[contains(text(), "PW\(123\)")]' => true,
      '//form[@id="allow-destroy-handler"]/input[@name="_pw_nonce" and @value="345"]' => true
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }
  }
}

?>