<?php

require_once('../classes/PWAdboxesClient.php');

class TestPWAdboxesClient extends PHPUnit_Framework_TestCase {
  private $database_client;
  private $sample_ad;

  function setUp() {
    global $wpdb;
    $this->database_client = new PWAdboxesClient();

    $this->sample_ad = (object)array('adboxid' => 1, 'sitename' => "a", 'url' => "http://meow.raow/",
                                     'dimensions' => "1x1", 'rating' => "a", 'category' => "a",
                                     'description' => "a", 'tags' => 'a', 'standardcode' => 'a',
                                     'advancedcode' => 'a', 'adtype' => 'a');
  }

  function testCreateTables() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('get_var'));
    $wpdb->prefix = "wp_";
    $wpdb->is_mock = true;

    $wpdb->expects($this->once())->method('get_var')->with($this->equalTo("SHOW TABLES LIKE {$this->database_client->table_name}"));

    $this->database_client->initialize();
  }

  function testDestroyTables() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('query'));
    $wpdb->prefix = "wp_";
    $wpdb->is_mock = true;

    $wpdb->expects($this->once())->method('query')->with($this->equalTo("DROP TABLE {$this->database_client->table_name}"));

    $this->database_client->destroy();
  }

  function testPostAds() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('escape', 'query'));
    $wpdb->prefix = "wp_";

    $ads = $this->getMock('PublisherInfo', array());
    $ads->member_id = "1";
    $ads->is_valid = true;

    $ads->adboxes = array($this->sample_ad);

    $wpdb->expects($this->exactly(11))->method('escape');
    $wpdb->expects($this->exactly(2))->method('query')->will($this->returnCallback(array($this, 'postAdsCallback')));

    $this->database_client->post_ads($ads, PW_ADBOXES_PROJECT_WONDERFUL);
  }

  function testRetrieveAds() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('get_results'));
    $wpdb->prefix = "wp_";

    $wpdb->expects($this->once())
         ->method('get_results')
         ->with($this->equalTo("SELECT * FROM {$this->database_client->table_name}"))
         ->will($this->returnValue(array($this->sample_ad)));

    $result = $this->database_client->get_ads("1");
    $this->assertType("PublisherInfo", $result);
  }

  function testCleanAds() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('query'));
    $wpdb->prefix = "wp_";

    $wpdb->expects($this->once())
         ->method('query')
         ->with($this->equalTo("DELETE FROM {$this->database_client->table_name}"));

    $this->database_client->clean_ads();
  }

  function testRetrieveAdsForSpecificType() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('get_results'));
    $wpdb->prefix = "wp_";

    $type = 10;

    $wpdb->expects($this->once())
         ->method('get_results')
         ->with($this->equalTo("SELECT * FROM {$this->database_client->table_name} WHERE type = {$type}"))
         ->will($this->returnValue(array()));

    $result = $this->database_client->get_ads("1", $type);
    $this->assertFalse($result);
  }

  function testRetrieveNoAds() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('get_results'));
    $wpdb->prefix = "wp_";

    $wpdb->expects($this->once())
         ->method('get_results')
         ->with($this->equalTo("SELECT * FROM {$this->database_client->table_name}"))
         ->will($this->returnValue(array()));

    $result = $this->database_client->get_ads("1");
    $this->assertFalse($result);
  }

  function testFilterTypeFromAd() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('escape', 'query'));
    $wpdb->prefix = "wp_";

    $ads = $this->getMock('PublisherInfo', array());
    $ads->member_id = "1";
    $ads->is_valid = true;

    $this->sample_ad->type = "3";
    $ads->adboxes = array($this->sample_ad);

    $wpdb->expects($this->exactly(2))->method('query')->will($this->returnCallback(array($this, 'postAdsFilterCallback')));

    $this->database_client->post_ads($ads, PW_ADBOXES_PROJECT_WONDERFUL);
  }

  function postAdsCallback($query) {
    if (strpos($query, "DELETE") === 0) {
      return $query == ("DELETE FROM {$this->database_client->table_name} WHERE type = " . PW_ADBOXES_PROJECT_WONDERFUL);
    } else {
      return true;
    }
  }

  function postAdsFilterCallback($query) {
    if (strpos($query, "INSERT") === 0) {
      return count(explode("type", $query)) === 3;
    } else {
      return true;
    }
  }
}

?>