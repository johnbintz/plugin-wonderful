<?php

define("PW_ADBOXES_PROJECT_WONDERFUL", 0);

/**
 * Information about an ad publisher.
 */
class PublisherInfo {
  var $parser, $current_type, $current_adbox, $is_valid, $memberid, $adboxes, $current_string;

  function PublisherInfo() {
    foreach (array('memberid', 'adboxes') as $param) {
      $this->{$param} = null;
    }
  }

  function parse($string, $type = PW_ADBOXES_PROJECT_WONDERFUL) {
    $this->parser = xml_parser_create();
    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, 'start_element_handler', 'end_element_handler');
    xml_set_character_data_handler($this->parser, 'character_data_handler');

    $this->current_type = $type;

    $this->is_valid = true;
    if (($result = xml_parse($this->parser, $string, true)) != 1) {
      $this->is_valid = false;
    }
    xml_parser_free($this->parser);

    if ($this->is_valid) {
      foreach (array('memberid', 'adboxes') as $required_parameter) {
        if (is_null($this->{$required_parameter})) {
          $this->is_valid = false; break;
        }
      }
    }

    return $this->is_valid;
  }

  function start_element_handler($parser, $name, $attributes) {
    $this->current_string = "";
    switch ($name) {
      case "PW:MEMBER":
        $valid = false;
        if (isset($attributes['MEMBERID'])) {
          if (is_numeric($attributes['MEMBERID'])) {
            $this->memberid = $attributes['MEMBERID'];
            $valid = true;
          }
        }
        $this->is_valid = $valid;
        break;
      case "PW:ADBOXES":
        $this->adboxes = array();
        break;
      case "PW:ADBOX":
        $new_attributes = array();
        $translated_attributes = array('TYPE' => 'adtype');
        foreach (array('ADBOXID', 'SITENAME', 'TYPE', 'URL', 'DIMENSIONS', 'RATING', 'CATEGORY') as $field) {
          if (!isset($attributes[$field])) {
            $this->is_valid = false; break;
          } else {
            $target_field = strtolower($field);
            if (isset($translated_attributes[$field])) { $target_field = $translated_attributes[$field]; }
            $new_attributes[$target_field] = $attributes[$field];
          }
        }
        if ($this->is_valid) {
          if (!is_numeric($attributes['ADBOXID'])) { $this->is_valid = false; break; }
          if (preg_match('#^[0-9]+x[0-9]+$#', $attributes['DIMENSIONS']) == 0) { $this->is_valid = false; break; }
          if (($result = parse_url($attributes['URL'])) === false) { $this->is_valid = false; break; }
          foreach (array('scheme', 'host') as $part) {
            if (!isset($result[$part])) { $this->is_valid = false; break; }
          }

          if ($this->is_valid) {
            $new_attributes['type'] = $this->current_type;
            $this->current_adbox = (object)$new_attributes;
          }
        }
        break;
    }
  }

  function end_element_handler($parser, $name) {
    switch ($name) {
      case "PW:ADBOX":
        foreach (array("description", "tags", "standardcode", "advancedcode") as $element) {
          if (!isset($this->current_adbox->{$element})) {
            $this->is_valid = false; break;
          }
        }
        if ($this->is_valid) { $this->adboxes[] = $this->current_adbox; }
        break;
      case "PW:DESCRIPTION":
      case "PW:TAGS":
      case "PW:STANDARDCODE":
      case "PW:ADVANCEDCODE":
        $short_name = strtolower(substr($name, 3));
        $this->current_adbox->{$short_name} = $this->current_string;
        break;
    }
  }

  function character_data_handler($parser, $data) {
    $this->current_string .= $data;
  }

  /**
   * Get information to create widgets.
   * @return array The widget information.
   */
  function get_sidebar_widget_info() {
    if ($this->is_valid) {
      $widgets = array();
      foreach ($this->adboxes as $adbox) {
        $widgets[] = array(
          "id" => "project_wonderful_{$this->memberid}_{$adbox->adboxid}",
          "name" => sprintf(__('PW %1$s %2$s %3$s (%4$s)', 'comicpress-manager'), $adbox->adtype, $adbox->dimensions, $adbox->sitename, $adbox->adboxid),
          "options" => array("adboxid" => $adbox->adboxid)
        );
      }

      return $widgets;
    } else {
      return false;
    }
  }
}

?>