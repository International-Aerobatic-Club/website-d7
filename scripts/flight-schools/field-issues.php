<?php

# DJM, 2016-01-27
# Script to help debug blank field issues

# Usage: drush -r $D7 -l www.iac.org scr field-issues.php


// Convenience var
$lang = LANGUAGE_NONE;

define('DRUPAL_ROOT', getenv('D7'));
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_DATABASE);


$result = db_query("SELECT nid FROM node WHERE type = 'flight_school'");

$nids = array();
foreach($result as $obj) {
  $nids[] = $obj->nid;
}

foreach($nids as $nid) {
  $node = node_load($nid);
  print $nid . ":\n";
  var_dump(sizeof($node->field_address2) > 0);
  print "\n";
}
