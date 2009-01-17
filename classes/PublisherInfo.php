<?php

class PublisherInfo {
  var $parser, $current_adbox, $is_valid, $memberid, $adboxes, $current_string;

  function PublisherInfo() {
    foreach (array('memberid', 'adboxes') as $param) {
      $this->{$param} = null;
    }
  }

  function parse($string) {
    $this->parser = xml_parser_create();
    xml_set_object($this->parser, $this);
    xml_set_element_handler($this->parser, 'start_element_handler', 'end_element_handler');
    xml_set_character_data_handler($this->parser, 'character_data_handler');

    $this->is_valid = true;
    if (($result = xml_parse($this->parser, $string, true)) != 1) { $this->is_valid = false; }
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
        foreach (array('ADBOXID', 'SITENAME', 'URL', 'DIMENSIONS', 'RATING', 'CATEGORY') as $field) {
          if (!isset($attributes[$field])) {
            $this->is_valid = false; break;
          } else {
            $new_attributes[strtolower($field)] = $attributes[$field];
          }
        }
        if ($this->is_valid) {
          if (!is_numeric($attributes['ADBOXID'])) { $this->is_valid = false; break; }
          if (preg_match('#^[0-9]+x[0-9]+$#', $attributes['DIMENSIONS']) == 0) { $this->is_valid = false; break; }
          if (($result = parse_url($attributes['URL'])) === false) { $this->is_valid = false; break; }
          foreach (array('scheme', 'host', 'path') as $part) {
            if (!isset($result[$part])) { $this->is_valid = false; break; }
          }

          if ($this->is_valid) {
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
}

?>