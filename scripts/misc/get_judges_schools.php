<?php

// Debug script for PHP errors in themes/iac3/node--judges-school.tpl.php
//   $prof->field_phone['und'][0]['safe_value']


// Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr get_judges_schools.php

$uids = Array();

$school_query = new EntityFieldQuery();
$school_results = $school_query->entityCondition('entity_type', 'node', '=')
  ->entityCondition('bundle', 'judges_school', "=")
  ->execute();

foreach($school_results['node'] as $school) {
		
	$s = node_load($school->nid);

	$instr_query = new EntityFieldQuery();
	$instr_results = $instr_query->entityCondition('entity_type', 'profile2', '=')
	  ->entityCondition('bundle', 'main', "=")
		->propertyCondition('uid', $s->field_assigned_to['und'][0]['target_id'], '=')
		->execute();

	$profile = entity_load('profile2', array_keys($instr_results['profile2']));

	$data = $s->field_address2;

	print "School node ID: " . $s->nid . ": ";
	print_r($data);
	print "\n";

}
