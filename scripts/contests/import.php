<?php

# DJM, 2015-03-04
# Import JSON contest data from https://iaccdb.iac.org/contests?year=nnnn

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr import.php yyyy
#   where yyyy is the desired year

// print(var_dump(node_load(3972))); exit(0);

$iaccdb = "https://iaccdb.iac.org";

$args = $GLOBALS['argv'];

$requested_year = $args[7];
if (empty($requested_year)) { $requested_year = date("Y"); }

$result = drupal_http_request("$iaccdb/contests.json?year=$requested_year");

if ($result->code == 404) {
  drupal_not_found();
} elseif ($result->code != 200) {
  print "<em> Lookup failed with result code $result->code </em>\n";
} else {

  $data = json_decode($result->data);
	$contests = $data->contests;

  foreach($data->contests as $contest) {

    $node = new stdClass();
    $node->title = "$contest->start $contest->name";
    $node->type = "contest";
    node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
    $node->language = LANGUAGE_NONE; // Or e.g. 'en' if locale is enabled
    $node->uid = 1;  // Admin, aka Webmaster
    $node->status = 1; //(1 or 0): published or not
    $node->promote = 0; //(1 or 0): promoted to front page
    $node->comment = 2; // 0 = comments disabled, 1 = read only, 2 = read/write

		$node->field_city[$node->language][0]['value'] = $contest->city;
		$node->field_state[$node->language][0]['value'] = $contest->state;
		$node->field_contest_dates[$node->language][0]['value'] = $contest->start;
		$node->field_contest_dates[$node->language][0]['value2'] = $contest->start;
		$node->field_iaccdb_id[$node->language][0]['value'] = $contest->id;

// director: add user entity ref
		if ($dir = user_load_by_name($contest->director)) {
			$node->field_director[$node->language][0]['target_id'] = $dir->uid;
			$node->field_director[$node->language][0]['target_type'] = "user";
		} else {
			print "Can't match contest director name: $contest->director for $contest->name, $contest->start\n";
		}


    switch ($contest->region) {
      case 'SouthWest':
        $r = 'Southwest';
        break;
      case 'NorthWest':
        $r = 'Northwest';
        break;
      case 'SouthWest':
        $r = 'Southwest';
        break;
      case 'SouthEast':
        $r = 'Southeast';
        break;
      case 'MidAmerica':
        $r = 'Mid-America';
        break;
  		case 'Nationals':
  			$r = '';
				$node->field_special_type[$node->language][0]['value'] = 'Nationals';
        break;
      default:
  			$r = '';
		}

		$node->field_region[$node->language][0]['value'] = $r;
    $node = node_submit($node); // Prepare node for saving
    node_save($node);

  }

}
