<?php

# DJM, 2015-03-12

# Calculate judge currency and update user records as appropriate,
# in accordance with the 2015 IAC Rule Book, Section 2.6.3

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr bulk_expire.php


$iaccdb = "https://iaccdb.iac.org";
# $iaccdb = "http://localhost:3002";

# Get all user profiles
$profile_query = new EntityFieldQuery();
$profile_query->entityCondition('entity_type', 'profile2', '=')
  ->entityCondition('bundle', 'main', "=");

$profile_results = $profile_query->execute();

if (empty($profile_results['profile2'])) {
	print "*** No user profiles found!? Something's wrong, exiting.\n";
	return;
}


$i = 0;

foreach($profile_results['profile2'] as $pid=>$ign) {
	$profiles = entity_load('profile2', Array($pid));
	foreach($profiles as $profile) {
		$i += 1;
		if (($i % 100) == 0) { print ("$i\n"); }
		$profile->field_current['und'][0]['value'] = '0';
		entity_save('profile2', $profile);
	}
}
