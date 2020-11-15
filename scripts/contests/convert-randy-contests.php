<?php

# DJM, 2015-03-04
# Import JSON contest data from CSV produced by convert-randy-contests.rb
# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr convert-randy-contests.php

# Handy constant
$lang = LANGUAGE_NONE;

# Inhale the CSV file
$csv = array_map("str_getcsv", file("/home/webmaster/scripts/contests/2015-contests.csv"));

# Discard headers
$columns = array_shift($csv);

# Discard first contest (Sebring)
$discard = array_shift($csv);

/*
   0 title
   1 airport_id
   2 airport_name
   3 city
   4 state
   5 region
   6 practice_start
   7 practice_end
   8 contest_start
   9 contest_end
  10 weather_start
  11 weather_end
  12 contest_director
  13 contact_info
  14 comments
*/

# Loop through the contests, building nodes as we go
foreach($csv as $contest) {
  
  $pstart = ($contest[6] == "" ? NULL : date('Y-m-d', strtotime($contest[6])));
  $pend   = ($contest[7] == "" ? NULL : date('Y-m-d', strtotime($contest[7])));
  $cstart = ($contest[8] == "" ? NULL : date('Y-m-d', strtotime($contest[8])));
  $cend   = ($contest[9] == "" ? NULL : date('Y-m-d', strtotime($contest[9])));
  $wstart = ($contest[10] == "" ? NULL : date('Y-m-d', strtotime($contest[10])));
  $wend   = ($contest[11] == "" ? NULL : date('Y-m-d', strtotime($contest[11])));

  $node = new stdClass();
  $node->title = "$cstart $contest[0]";
  $node->type = "contest";

  node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().

  $node->language = $lang;
  $node->uid = 1;  // Admin, aka Webmaster
  $node->status = 1; //(1 or 0): published or not
  $node->promote = 0; //(1 or 0): promoted to front page
  $node->comment = 2; // 0 = comments disabled, 1 = read only, 2 = read/write

  $node->field_airport_id[$lang][0]['value'] = $contest[1];
  $node->field_airport_name[$lang][0]['value'] = $contest[2];

  $node->field_city[$lang][0]['value'] = $contest[3];
  $node->field_state[$lang][0]['value'] = $contest[4];

  if (isset($pstart)) { $node->field_practice_registration_date[$lang][0]['value'] = $pstart; }
  if (isset($pend)) { $node->field_practice_registration_date[$lang][0]['value2'] = $pend; }

  $node->field_contest_dates[$lang][0]['value'] = $cstart;
  if (isset($cend)) { $node->field_contest_dates[$lang][0]['value2'] = $cend; }

  if (isset($wstart)) { $node->field_weather_dates[$lang][0]['value'] = $wstart; }
  if (isset($wend)) { $node->field_weather_dates[$lang][0]['value2'] = $wend; }

  $node->field_contact_info[$lang][0]['value'] = $contest[13];

  $node->body[$lang][0]['value'] = $contest[14];

// director: add user entity ref
  if ($dir = user_load_by_name($contest[12])) {
    $node->field_contest_director[$lang][0]['target_id'] = $dir->uid;
    $node->field_contest_director[$lang][0]['target_type'] = "user";
  } else {
    print "Can't match contest director name: $contest[12] for $contest[0]\n";
  }

  $node->field_region[$lang][0]['value'] = $contest[5];

  $node = node_submit($node); // Prepare node for saving
  node_save($node);

}
