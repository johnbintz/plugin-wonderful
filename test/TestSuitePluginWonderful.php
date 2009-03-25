<?php

require_once('TestPWAdboxesClient.php');
require_once('TestPublisherInfo.php');

class TestPluginWonderful {
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite();
    $suite->addTestSuite(new ReflectionClass("TestPWAdboxesClient"));
    $suite->addTestSuite(new ReflectionClass("TestPublisherInfo"));
    return $suite;
  }
}

?>