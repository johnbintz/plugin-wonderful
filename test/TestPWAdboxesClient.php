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
                                     'advancedcode' => 'a', 'adtype' => 'a', 'template_tag_id' => 'a',
                                     'in_rss_feed' => 0);
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
    $wpdb = $this->getMock('wpdb', array('escape', 'query', 'get_results'));
    $wpdb->prefix = "wp_";

    $ads = $this->getMock('PublisherInfo', array());
    $ads->member_id = "1";
    $ads->is_valid = true;

    $ads->adboxes = array($this->sample_ad);

    $wpdb->expects($this->exactly(13))->method('escape');
    $wpdb->expects($this->exactly(2))->method('query')->will($this->returnCallback(array($this, 'postAdsCallback')));
    $wpdb->expects($this->exactly(1))->method('get_results')->will($this->returnValue(array()))->with("SELECT adboxid, template_tag_id, in_rss_feed FROM {$this->database_client->table_name}");

    $this->database_client->post_ads($ads, PW_ADBOXES_PROJECT_WONDERFUL);
  }

  function testRetrieveAds() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('get_results'));
    $wpdb->prefix = "wp_";

    $wpdb->expects($this->once())
         ->method('get_results')
         ->with($this->equalTo("SELECT * FROM {$this->database_client->table_name} ORDER BY adboxid ASC"))
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
         ->with($this->equalTo("SELECT * FROM {$this->database_client->table_name} WHERE type = {$type} ORDER BY adboxid ASC"))
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
         ->with($this->equalTo("SELECT * FROM {$this->database_client->table_name} ORDER BY adboxid ASC"))
         ->will($this->returnValue(array()));

    $result = $this->database_client->get_ads("1");
    $this->assertFalse($result);
  }

  function testFilterTypeFromAd() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('escape', 'query', 'get_results'));
    $wpdb->prefix = "wp_";

    $ads = $this->getMock('PublisherInfo', array());
    $ads->member_id = "1";
    $ads->is_valid = true;

    $this->sample_ad->type = "3";
    $ads->adboxes = array($this->sample_ad);

    $wpdb->expects($this->exactly(2))->method('query')->will($this->returnCallback(array($this, 'postAdsFilterCallback')));

    $this->database_client->post_ads($ads, PW_ADBOXES_PROJECT_WONDERFUL);
  }

  function testUseTemplateTags() {
    global $wpdb;
    $wpdb = $this->getMock('wpdb', array('escape', 'query', 'get_results'));
    $wpdb->prefix = "wp_";

    $ads = $this->getMock('PublisherInfo', array());
    $ads->member_id = "1";
    $ads->is_valid = true;

    $ads->adboxes = array($this->sample_ad);

    $test_tag = "my_tag";

    $wpdb->expects($this->any())->method('escape')->will($this->returnCallback(array($this, 'useTemplateTagsEscapeCallback')));
    $wpdb->expects($this->any())->method('query')->will($this->returnCallback(array($this, 'useTemplateTagsCallback')));
    $wpdb->expects($this->any())->method('get_results')->will($this->returnCallback(array($this, 'useTemplateTagsCallback')));

    $this->assertTrue($this->database_client->post_ads($ads, PW_ADBOXES_PROJECT_WONDERFUL));

    $this->target_ad = $this->sample_ad;
    $this->target_ad->template_tag_id = $test_tag;

    $this->assertFalse($this->database_client->set_template_tag(0, $test_tag));

    $this->assertTrue($this->database_client->set_template_tag(1, $test_tag));
    $this->assertEquals($this->target_ad, $this->database_client->get_ad_by_template_tag(1, $test_tag));

    $this->has_set_template_tag = true;

    $this->assertTrue($this->database_client->post_ads($ads, PW_ADBOXES_PROJECT_WONDERFUL));
    $this->assertEquals($this->target_ad, $this->database_client->get_ad_by_template_tag(1, $test_tag));
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

  function useTemplateTagsCallback($query) {
    if (strpos($query, "adboxid = '0'") !== false) {
      return array();
    }
    if (strpos($query, "SELECT * FROM pw_adboxes") !== false) {
      return array($this->target_ad);
    }
    if (strpos($query, "SELECT adboxid, template_tag_id, in_rss_feed") !== false) {
      if ($this->has_set_template_tag) {
        $info = new stdClass();
        $info->adboxid = "1";
        $info->template_tag_id = "my_tag";
        $info->in_rss_feed = "0";

        return array($info);
      } else {
        return array();
      }
    }
    if (strpos($query, "INSERT INTO pw_adboxes") !== false) {
      if ($this->has_set_template_tag) {
        return (strpos($query, "my_tag") > 0) ? 1 : false;
      } else {
        return 1;
      }
    }
    return array("item");
  }

  function useTemplateTagsEscapeCallback($value) {
    return $value;
  }
}

?>