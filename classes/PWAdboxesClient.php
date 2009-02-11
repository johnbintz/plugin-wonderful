<?php

require_once('PublisherInfo.php');

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
                 rating char(10) NOT NULL,
                 category char(30) NOT NULL,
                 description text NOT NULL,
                 tags text NOT NULL,
                 standardcode text NOT NULL,
                 advancedcode text NOT NULL
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
        $wpdb->query("DELETE FROM {$this->table_name} WHERE type = $type");
        foreach ($ads->adboxes as $box) {
          $columns = array("type");
          $values  = array($type);

          foreach ((array)$box as $key => $value) {
            if ($key !== "type") {
              $columns[] = $key;
              $values[]  = '"' . $wpdb->escape($value) . '"';
            }
          }

          $wpdb->query("INSERT INTO {$this->table_name} (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")");
        }
      }
    }
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
   * Remove all ads from the database.
   */
  function clean_ads() {
    global $wpdb;

    $wpdb->query("DELETE FROM {$this->table_name}");
  }
}

?>