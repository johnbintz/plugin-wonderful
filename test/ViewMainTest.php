<?php

require_once('PHPUnit/Framework.php');
require_once(dirname(__FILE__) . '/../views/PluginWonderfulViewMain.php');
require_once(dirname(__FILE__) . '/../../mockpress/mockpress.php');

class ViewMainTest extends PHPUnit_Framework_TestCase {
  function setUp() {
    _reset_wp();
    $this->m = new PluginWonderfulViewMain();
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

  /**
   * @dataProvider providerTestRenderMemberIDSettings
   */
  function testRenderMemberIDSettings($options) {
    foreach ($options as $key => $value) {
      update_option($key, $value);
    }
    
    ob_start();
    $this->m->_render_memberid_settings();
    $source = ob_get_clean();
    
    $this->assertTrue(!empty($source));
    $this->assertTrue(($xml = _to_xml($source, true)) !== false);
    
    foreach (array(
      '//input[@id="memberid" and @value="123"]' => true,
      '//input[@name="use-standardcode" and ' . (($options['plugin-wonderful-use-standardcode'] == 1) ? '@checked' : 'not(@checked)') . ']' => true,
      '//input[@name="enable-body-copy-embedding" and ' . (($options['plugin-wonderful-enable-body-copy-embedding'] == 1) ? '@checked' : 'not(@checked)') . ']' => true,
    ) as $xpath => $value) {
      $this->assertTrue(_xpath_test($xml, $xpath, $value), $xpath);
    }
  }
}

?>