<?php

require_once('PublisherInfo.php');

define("PLUGIN_WONDERFUL_DATABASE_VERSION", 3);

/**
 * The interface to the PW database table.
 */
class PWAdboxesClient {
  var $table_name;
  function PWAdboxesClient() {
    global $wpdb;

    $this->table_name = $wpdb->prefix . "pw_adboxes";
    $this->table_exists = false;
  }

  /**
   * Initialize the table if it doesn't exist.
   */
  function initialize() {
    global $wpdb;

    if ($wpdb->get_var("SHOW TABLES LIKE {$this->table_name}") != $this->table_name) {
      if (!$wpdb->is_mock) {
        $sql = "CREATE TABLE {$this->table_name} (
                 type int(1) NOT NULL,
                 adboxid int(11) NOT NULL,
                 sitename char(100) NOT NULL,
                 adtype char(30) NOT NULL,
                 url char(255) NOT NULL,
                 dimensions char(10) NOT NULL,
                 rating char(30) NOT NULL,
                 category char(50) NOT NULL,
                 description text NOT NULL,
                 tags text NOT NULL,
                 standardcode text NOT NULL,
                 advancedcode text NOT NULL,
                 template_tag_id char(30),
                 in_rss_feed int(1)
               );";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
      }
    }
  }

  /**
   * Destroy the table.
   */
  function destroy() {
    global $wpdb;

    $wpdb->query("DROP TABLE {$this->table_name}");
  }

  /**
   * Post PublisherInfo to the database.
   * @param PublisherInfo $ads The PublisherInfo to post to the database.
   * @param integer $type The ad type.
   */
  function post_ads($ads, $type = PW_ADBOXES_PROJECT_WONDERFUL) {
    global $wpdb;

    if (is_a($ads, 'PublisherInfo')) {
      if ($ads->is_valid) {
        $mappings = array();

        if (is_array($results = $wpdb->get_results("SELECT adboxid, template_tag_id, in_rss_feed FROM {$this->table_name}"))) {
          foreach ($results as $result) {
            $mappings[$result->adboxid] = $result;
          }
        }

        $wpdb->query("DELETE FROM {$this->table_name} WHERE type = $type");
        foreach ($ads->adboxes as $box) {
          $columns = array("type");
          $values  = array($type);

          if (isset($mappings[$box->adboxid])) {
            foreach ((array)$mappings[$box->adboxid] as $key => $value) {
              $box->{$key} = $value;
            }
          }

          foreach ((array)$box as $key => $value) {
            if ($key !== "type") {
              $columns[] = $key;
              $values[]  = '"' . $wpdb->escape($value) . '"';
            }
          }

          if (!$wpdb->query("INSERT INTO {$this->table_name} (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")")) {
            return false;
          }
        }
        return true;
      }
    }

    return false;
  }

  function _handle_ad_retrieval($member_id, $query) {
    global $wpdb;

    if (count($results = $wpdb->get_results($query)) > 0) {
      $ads = new PublisherInfo();
      $ads->memberid = $member_id;
      $ads->adboxes = $results;
      $ads->is_valid = true;

      return $ads;
    }

    return false;
  }

  /**
   * Retrieve all ads from the database and create a new PublisherInfo object.
   * @param integer $member_id The Project Wonderful member ID to use.
   * @return PublisherInfo The PublisherInfo object for the ads, or false if no ads are found.
   */
  function get_ads($member_id, $type = null) {
    global $wpdb;

    $query = "SELECT * FROM {$this->table_name}";
    if (!is_null($type)) { $query .= " WHERE type = {$type}"; }
    $query .= " ORDER BY adboxid ASC";

    return $this->_handle_ad_retrieval($member_id, $query);
  }

  /**
   * Remove all ads from the database.
   */
  function clean_ads() {
    global $wpdb;

    $wpdb->query("DELETE FROM {$this->table_name}");
  }

  /**
   * Set the template tag id for an advertisement.
   */
  function set_template_tag($adboxid, $tag) {
    global $wpdb;

    $query  = "UPDATE {$this->table_name} SET ";
    $query .= "template_tag_id = '" . $wpdb->escape($tag) . "'";
    $query .= " WHERE adboxid = '" . $wpdb->escape($adboxid) . "'";
    $query .= " ORDER BY adboxid ASC";

    $result = $wpdb->get_results($query);
    return count($result) > 0;
  }

  /**
   * Get an adbox by template tag id.
   */
  function get_ad_by_template_tag($member_id, $tag) {
    global $wpdb;

    $query = "SELECT * FROM {$this->table_name} WHERE template_tag_id = '" . $wpdb->escape($tag) . "'";

    if (($result = $this->_handle_ad_retrieval($member_id, $query)) !== false) {
      return reset($result->adboxes);
    } else {
      return false;
    }
  }

  /**
   * Enable or disable RSS feed usage.
   */
  function set_rss_feed_usage($adboxid, $status = false) {
    global $wpdb;

    $query  = "UPDATE {$this->table_name} SET ";
    $query .= "in_rss_feed = '" . ($status ? 1 : 0) . "'";
    $query .= " WHERE adboxid = '" . $wpdb->escape($adboxid) . "'";

    $result = $wpdb->get_results($query);
    return count($result) > 0;
  }
}

?>
