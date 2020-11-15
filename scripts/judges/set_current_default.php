<?php

# DJM, 2015-03-28

# Initialize 'current' field for users for whom it is not yet set

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr set_current_default.php


######################### Get all profile IDs

$profile_result = db_query("SELECT pid FROM profile ORDER BY pid;");

// Store the iac_number-to-uid associations in an associative array
$plist = Array();
foreach ($profile_result as $rec) { $plist[] = $rec->pid; }

foreach($plist as $pid) {
  $profile = profile2_load($pid);
  if (empty($profile->field_current)) {
    $profile->field_current['und'][0]['value'] = "0";
    profile2_save($profile);
    print "$profile->pid\n";
  }
}
