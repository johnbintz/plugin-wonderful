<?php

require_once('PublisherInfo.php');

define("PLUGIN_WONDERFUL_DATABASE_VERSION", 5);

/**
 * The interface to the PW database table.
 */
class PWAdboxesClient {
  var $table_name;
  function PWAdboxesClient() {
    global $wpdb;

    $this->table_name = $wpdb->prefix . "pw_adboxes";
    $this->table_exists = false;

    $this->schema_info = array(
      array('type', 'int', '1', "NOT NULL"),
      array('adboxid', 'int', '11', "NOT NULL"),
      array('sitename', 'char', '100', 'NOT NULL'),
      array('adtype', 'char', '30', 'NOT NULL'),
      array('url', 'char', '255', 'NOT NULL'),
      array('dimensions', 'char', '10', 'NOT NULL'),
      array('rating', 'char', '30', 'NOT NULL'),
      array('category', 'char', '50', 'NOT NULL'),
      array('description', 'text', '', 'NOT NULL'),
      array('tags', 'text', '', 'NOT NULL'),
      array('standardcode', 'text', '', 'NOT NULL'),
      array('advancedcode', 'text', '', 'NOT NULL'),
      array('template_tag_id', 'char', '30', ''),
      array('in_rss_feed', 'int', '1', '')
    );
  }

  /**
   * Initialize the table if it doesn't exist.
   */
  function initialize($force = false) {
    global $wpdb;

    if (($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") != $this->table_name) || $force) {
      $sql = "CREATE TABLE {$this->table_name} (\n";

      $statements = array();
      foreach ($this->schema_info as $info) {
        list($name, $type, $size, $extra) = $info;
        $statement = "{$name} {$type}";
        if (!empty($size)) { $statement .= "({$size})"; }
        if (!empty($extra)) { $statement .= " {$extra}"; }
        $statements[] = $statement;
      }

      $sql .= implode(",\n", $statements) . ");";

      if (!$wpdb->is_mock) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        return true;
      }
    }

    return false;
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

        if (is_array($results = $wpdb->get_results("SELECT adboxid, template_tag_id, in_rss_feed, center_widget FROM {$this->table_name}"))) {
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

          foreach ($this->schema_info as $info) {
            list($key, $column_type, $size, $extra) = $info;

            if ($key !== "type") {
              $columns[] = $key;
              $value = $box->{$key};
              if (!empty($size)) { $value = substr($value, 0, $size); }
              if (empty($value)) {
                switch ($column_type) {
                  case "int": $value = 0; break;
                }
              }
              $values[]  = '"' . $wpdb->escape($value) . '"';
            }
          }

          $sql = "INSERT INTO {$this->table_name} (" . implode(",", $columns) . ") VALUES (" . implode(",", $values) . ")";

          if (!$wpdb->query($sql)) {
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

  function trim_field($field, $value) {
    foreach ($this->schema_info as $info) {
      list($key, $type, $size, $extra) = $info;
      if ($key == $field) {
        $value = substr($value, 0, $size); break;
      }
    }
    return $value;
  }

  /**
   * Set the template tag id for an advertisement.
   */
  function set_template_tag($adboxid, $tag) {
    global $wpdb;

    $tag = $this->trim_field('template_tag_id', $tag);

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

  function _handle_toggle($column, $adboxid, $status = false) {
    global $wpdb;

    $query  = "UPDATE {$this->table_name} SET ";
    $query .= "{$column} = '" . ($status ? 1 : 0) . "'";
    $query .= " WHERE adboxid = '" . $wpdb->escape($adboxid) . "'";

    $result = $wpdb->get_results($query);
    return count($result) > 0;
  }

  /**
   * Enable or disable RSS feed usage.
   */
  function set_rss_feed_usage($adboxid, $status = false) {
    return $this->_handle_toggle("in_rss_feed", $adboxid, $status);
  }

  /**
   * Enable or disable widget centering.
   */
  function set_widget_centering($adboxid, $status = false) {
    return $this->_handle_toggle("center_widget", $adboxid, $status);
  }
}

?>
