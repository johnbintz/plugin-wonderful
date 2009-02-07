<?php

require_once('../classes/PublisherInfo.php');

class TestPublisherInfo extends PHPUnit_Framework_TestCase {
  private $parser, $default_data;

  public function setup() {
    $this->parser = new PublisherInfo();

    $this->default_data = array(
      array("3", 'adboxid'),
      array("a", 'sitename'),
      array("http://meow.raow/", 'url'),
      array("1x1", 'dimensions'),
      array("a", 'rating'),
      array("a", 'description'),
      array("a", 'tags'),
      array("a", 'standardcode'),
      array("a", 'advancedcode'),
      array(PW_ADBOXES_PROJECT_WONDERFUL, "type")
    );
  }

  public static function badDataProvider() {
    return array(
      array("</test>"),
      array("?xml version=\"1.0\""),
      array('<pw:member></pw:member>'),
      array('<pw:member memberid="1"></pw:member>'),
      array('<pw:member memberid="meow"><pw:adboxes></pw:adboxes></pw:member>'),
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox /></pw:adboxes></pw:member>'),
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="1" sitename="a" url="http://meow" dimensions="1x1" rating="a" category="a" /></pw:adboxes></pw:member>'),
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="meow" sitename="a" url="http://meow" dimensions="1x1" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>'),
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="1" sitename="a" url="http://meow" dimensions="a" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>'),
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="1" sitename="a" url="a" dimensions="1x1" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>')
    );
  }

  /**
   * @dataProvider badDataProvider
   */
  public function testBadPWData($string) {
    $this->assertFalse($this->parser->parse($string));
  }

  public static function goodDataProvider() {
    return array(
      array('<pw:member memberid="1"><pw:adboxes></pw:adboxes></pw:member>'),
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="5" sitename="a" url="http://meow.raow/" dimensions="1x1" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>')
    );
  }

  /**
   * @dataProvider goodDataProvider
   */
  public function testGoodPWData($string) {
    $this->assertTrue($this->parser->parse($string));
  }

  public function testPWAPI() {
    $this->parser->parse('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="3" sitename="a" url="http://meow.raow/" dimensions="1x1" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>');

    $this->assertEquals(1, $this->parser->memberid);
    $this->assertEquals(1, count($this->parser->adboxes));

    foreach ($this->default_data as $info) {
      list($value, $param) = $info;

      $this->assertEquals($value, $this->parser->adboxes[0]->{$param}, $param);
    }
  }

  function testGetSidebarInformation() {
    $this->parser->is_valid = true;

    $this->parser->memberid = "1";

    $default_data_as_hash = array();
    foreach ($this->default_data as $info) {
      list($value, $param) = $info;
      $default_data_as_hash[$param] = $value;
    }
    $this->parser->adboxes = array((object)$default_data_as_hash);

    $sidebar_info = array(
      array(
        "id" => "project_wonderful_1_{$default_data_as_hash['adboxid']}",
        "name" => "PW {$default_data_as_hash['dimensions']} {$default_data_as_hash['sitename']} ({$default_data_as_hash['adboxid']})",
        "options" => array("adboxid" => $default_data_as_hash['adboxid'])
      )
    );

    $this->assertEquals($sidebar_info, $this->parser->get_sidebar_widget_info());

    $this->parser->is_valid = false;

    $this->assertFalse($this->parser->get_sidebar_widget_info());
  }

  function testChangeSidebarAdType() {
    $this->parser->is_valid = true;

    $this->parser->memberid = "1";

    $default_data_as_hash = array();
    foreach ($this->default_data as $info) {
      list($value, $param) = $info;
      $default_data_as_hash[$param] = $value;
    }
    $this->parser->adboxes = array((object)$default_data_as_hash);
  }
}

function __($string, $domain) { return $string; }

?>