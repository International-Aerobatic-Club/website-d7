<?php

# DJM, 2016-03-22
# Import JSON judge experience data from https://iaccdb.iac.org/judge/activity/nnnn

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr import.php yyyy, where yyyy is the desired year

$iaccdb = "https://iaccdb.iac.org";
# $iaccdb = "http://localhost:3002";

# Hash for holding contest numbers that don't match any Contest nodes
$unmatched_contests = Array();

# Utility function
function etime($s) { return number_format(microtime(TRUE) - $s, 2); }

// $args = $GLOBALS['argv'];

$requested_year = $args[1];
if (empty($requested_year)) { $requested_year = date("Y"); }



// Grab iac_number/uid pairs by keying off the data field that contains IAC
// numbers, then join the profile table
// TODO: Use the Drupal API instead

$member_result = db_query("
  SELECT field_iac_number_value as iac_number, uid
    FROM field_data_field_iac_number, profile
    WHERE entity_id = pid
      AND field_iac_number_value > 0
      AND field_iac_number_value < 99990000
      AND entity_type = 'profile2'
");

// Store the iac_number-to-uid associations in an associative array
$umap = Array();
foreach ($member_result as $rec) { $umap[$rec->iac_number] = $rec->uid; }



# Retrieve the judges activity report for the requested year
print "Retrieving experience records from IACCDB... ";
$start_time = microtime(TRUE);
$ja_result = drupal_http_request("$iaccdb/judge/activity/$requested_year", Array('timeout' => 300));
print "done (" . etime($start_time) . " sec elapsed).\n";

if ($ja_result->code == 404) {
  print "Lookup returned 404 - page not found. Exiting.\n";
  return;
} elseif ($ja_result->code != 200) {
  print "Lookup failed with result code $ja_result->code\n";
  return;
}

# Decode the result and extract the Acvitity associative array
$members = json_decode($ja_result->data)->Activity;

# Build a list of unique IACCDB Contest ID values
$contest_ids = Array();
foreach($members as $member) {
  foreach ($member as $experience) {
    $contest_ids[$experience[0]] = 1;
  }
}

$contests = Array();

if (count($contest_ids) > 0) {

  # Get all contest records that appear in the list of IACCDB ID values
  $contest_query = new EntityFieldQuery();
  $contest_query->entityCondition('entity_type', 'node', "=")
    ->entityCondition('bundle', 'contest', "=")
    ->propertyCondition('status', NODE_PUBLISHED)
    ->fieldCondition('field_iaccdb_id', 'value', array_keys($contest_ids), "IN")
    ->propertyOrderBy('title', 'value');

  # Fire off the query
  $contest_results = $contest_query->execute();

  # If the query returned any records, save the nids then load all of the contest nodes
  if (isset($contest_results['node'])) {
    $contest_nids = array_keys($contest_results['node']);
    $contests = entity_load('node', $contest_nids);
  } else {
    print "No contests retrieved, exiting.\n";
    return;
  }

}



# Build a map of IACCDB Contest ID values -> contest nids
$cmap = Array();
foreach($contests as $contest) { $cmap[$contest->field_iaccdb_id['und'][0]['value']] = intval($contest->nid); }




# Now that we're all teed up, let's delete any previously uploaded records for the requested year
# Step 1: Get the list of contest entity IDs for the requested year
$contest_query = new EntityFieldQuery();
$contest_query->entityCondition('entity_type', 'node', "=")
  ->entityCondition('bundle', 'contest', "=")
  ->propertyCondition('status', NODE_PUBLISHED)
  ->fieldCondition('field_contest_dates', 'value', "$requested_year-01-01", ">=")
  ->fieldCondition('field_contest_dates', 'value', "$requested_year-12-31", "<=");

$contest_results = $contest_query->execute();


# Step 2: Delete all judging_experience nodes that refer to those contests
if (count($contest_results) > 0) {
  $je_query = new EntityFieldQuery();
  $je_query->entityCondition('entity_type', 'node', "=")
    ->entityCondition('bundle', 'judging_experience', "=")
    ->fieldCondition('field_contest', 'target_id', array_keys($contest_results['node']), "IN");

  $je_results = $je_query->execute();

  if (isset($je_results['node'])) { $delete_nids = array_keys($je_results['node']); }

  $start_time = microtime(TRUE);
  print "Deleting " . sizeof($delete_nids) . " previous records from $requested_year...";
  node_delete_multiple($delete_nids);
  print " done (" . etime($start_time) . " sec elapsed).\n";
}




# Counters
$rec_count = 0;
$mem_count = 0;

$start_time = microtime(TRUE);

# Now, at last, process the experience records for each member
foreach($members as $member=>$experience) {

  # Find the corresponding uid; skip if no match found
  $uid = $umap[$member];
  if (empty($uid)) { continue; }

  # Get the corresponding user
  $u = user_load($uid);

  $mem_count += 1;

#  print "$u->name:\n";
  foreach($experience as $e ) {

    # Convenience vars
    $cid = $cmap[$e[0]];
    $lang = LANGUAGE_NONE;

    # Record and skip contests that are not yet in our database
    if (empty($cid)) {
      $unmatched_contests[$e[0]] = 1;
#      print "Skipping contest $e[0]\n";
      continue;
    }

    # Display a human-readable version of each experience item
#    print "  Contest: $e[0], Role: $e[1], Nats: ";
#    if ($e[2]) { print "Y"; } else { print "N"; }
#    print ", Cat: $e[3], Flt: $e[4], Qty: $e[5]\n";


    # Basic node setup
    $node = new stdClass();
    $node->title = implode(' / ', Array($u->name, $cid, $e[1]));
    $node->type = "judging_experience";
    node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
    $node->language = $lang; // Or e.g. 'en' if locale is enabled
    $node->uid = 1;  // Admin, aka Webmaster
    $node->status = 1; //(1 or 0): published or not
    $node->promote = 0; //(1 or 0): promoted to front page
    $node->comment = 2; // 0 = comments disabled, 1 = read only, 2 = read/write



    # Now add the fields
    # Add user entity ref
    $node->field_member[$lang][0]['target_id'] = $uid;
    $node->field_member[$lang][0]['target_type'] = 'user';

    # Add contest entity ref
    $node->field_contest[$lang][0]['target_id'] = $cid;
    $node->field_contest[$lang][0]['target_type'] = 'node';

    # The remaining fields have normal textual data
    $node->field_role[$lang][0]['value'] = $e[1];
    $node->field_nationals[$lang][0]['value'] = ($e[2] ? "1" : "0");
    $node->field_category[$lang][0]['value'] = $e[3];
    $node->field_flight[$lang][0]['value'] = $e[4];
    $node->field_number_of_flights[$lang][0]['value'] = $e[5];


    # Prepare the node and save it
    $node = node_submit($node);
    node_save($node);

    $rec_count += 1;

  }

}



### Summary printouts
print "Added $rec_count contest-related records for " . $mem_count . " members (" . etime($start_time) . " sec elapsed).\n";
if (!empty($unmatched_contests)) {
  print "*** Unmatched Contest IDs: " . implode(', ', array_keys($unmatched_contests)) . "\n";
}
print "\n";
