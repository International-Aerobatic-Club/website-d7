<?php

# DJM, 2015-03-26
# Make someone a judge (Regional or National). This script is intended for automating bulk
# operations and probably doesn't need to be used after the initial import of judges

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr make_judge.php iacno type
#   where iacno is the number of the member in question and type is either 'Regional' or 'National'

$args = $GLOBALS['argv'];

// In a PHP script invoked via drush, $args[7] is the first user-supplied argument
$iacno = $args[7];
$judge_type = $args[8];

if (empty($iacno) || empty($args[8])) {
  print "Usage: make_judge.php iacno type\n";
  exit(1);
}

if (!in_array($judge_type, Array('Regional', 'Regional-N', 'National'))) {
  print "Judge type must be Regional, Regional-N, or National\n";
  exit(2);
}


// Grab the member's profile

$profile_query = new EntityFieldQuery();
$profile_results = $profile_query->entityCondition('entity_type', 'profile2', '=')
  ->entityCondition('bundle', 'main', '=')
  ->fieldCondition('field_iac_number', 'value', $iacno, '=')
  ->execute();

if (empty($profile_results['profile2'])) {
  print "$iacno: No such record\n";
  return;
}


// Get the first entry from the array of results and load the corresponding profile
$prof = profile2_load(current($profile_results['profile2'])->pid);

// Set the judge type
if (empty($prof->field_judge['und'][0]['value'])) {
  print "$iacno: Changing '" . $prof->field_judge['und'][0]['value'] . "' to $judge_type\n";
  $prof->field_judge['und'][0]['value'] = $judge_type;
} elseif ($prof->field_judge['und'][0]['value'] != $judge_type) {
  print "$iacno: *** MISMATCH ***  old=" . $prof->field_judge['und'][0]['value'] . ", new=$judge_type\n";
} else {
  print "$iacno: Judge type is already correct ($judge_type)\n";
}

// Make the user as current
# $prof->field_current['und'][0]['value'] = "1";

// Save the changes
profile2_save($prof);
