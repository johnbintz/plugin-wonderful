<?php

require_once('../classes/PublisherInfo.php');

class TestPublisherInfo extends PHPUnit_Framework_TestCase {
  private $parser;

  public function setup() {
    $this->parser = new PublisherInfo();
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
      array('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="1" sitename="a" url="http://meow.raow/" dimensions="1x1" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>')
    );
  }

  /**
   * @dataProvider goodDataProvider
   */
  public function testGoodPWData($string) {
    $this->assertTrue($this->parser->parse($string));
  }

  public function testPWAPI() {
    $this->parser->parse('<pw:member memberid="1"><pw:adboxes><pw:adbox adboxid="1" sitename="a" url="http://meow.raow/" dimensions="1x1" rating="a" category="a"><pw:description>a</pw:description><pw:tags>a</pw:tags><pw:standardcode>a</pw:standardcode><pw:advancedcode>a</pw:advancedcode></pw:adbox></pw:adboxes></pw:member>');

    $this->assertEquals(1, $this->parser->memberid);
    $this->assertEquals(1, count($this->parser->adboxes));
    $this->assertEquals(1, $this->parser->adboxes[0]->adboxid);
    $this->assertEquals("a", $this->parser->adboxes[0]->sitename);
    $this->assertEquals("http://meow.raow/", $this->parser->adboxes[0]->url);
    $this->assertEquals("1x1", $this->parser->adboxes[0]->dimensions);
    $this->assertEquals("a", $this->parser->adboxes[0]->rating);
    $this->assertEquals("a", $this->parser->adboxes[0]->description);
    $this->assertEquals("a", $this->parser->adboxes[0]->tags);
    $this->assertEquals("a", $this->parser->adboxes[0]->standardcode);
    $this->assertEquals("a", $this->parser->adboxes[0]->advancedcode);
  }
}

?>