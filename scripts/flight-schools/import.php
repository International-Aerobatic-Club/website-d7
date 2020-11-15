<?php

# DJM, 2015-07-30
# Import CSV flight school info and store as Drupal nodes

# Usage: drush -r $D7 -l www.iac.org scr import.php < schools.csv


// Convenience var
$lang = LANGUAGE_NONE;

// Record counter (duh)
$rec_count = 0;

// Read (and ignore) the CSV file header
$ign = fgetcsv(STDIN);

// Inhale the rest of the CSV file and store
// the fields in an array of associative arrays

while ($row = fgetcsv(STDIN)) {

  # Basic node setup
  $node = new stdClass();
  $node->title = $row[1];
  $node->type = "flight_school";
  node_object_prepare($node); // Sets some defaults. Invokes hook_prepare() and hook_node_prepare().
  $node->language = $lang;
  $node->uid = 1;  // Admin, aka Webmaster
  $node->status = 1; //(1 or 0): published or not
  $node->promote = 0; //(1 or 0): promoted to front page
  $node->comment = 0; // 0 = comments disabled, 1 = read only, 2 = read/write

  # Now add the fields
  #  0,   1,           2,           3,   4,    5,  6,      7,    8,  9,   10, 11,      12,    13,   14,     15,     16      17
  # id,name,addressLine2,addressLine2,city,state,zip,contact,phone,fax,email,web,aircraft,course,notes,airport,country, instrs
  $node->field_street_address[$lang][0]['value'] = $row[2];
  $node->field_address2[$lang][0]['value'] = $row[3];
  $node->field_city[$lang][0]['value'] = $row[4];
  $node->field_state[$lang][0]['value'] = $row[5];
  $node->field_zip_code[$lang][0]['value'] = $row[6];
  $node->field_country[$lang][0]['value'] = $row[16];
  $node->field_contact_person[$lang][0]['value'] = $row[7];
  $node->field_phone[$lang][0]['value'] = $row[8];
  $node->field_fax[$lang][0]['value'] = $row[9];
  $node->field_email[$lang][0]['value'] = $row[10];
  $node->field_web[$lang][0]['value'] = $row[11];
  $node->field_aircraft[$lang][0]['value'] = $row[12];
  $node->field_courses[$lang][0]['value'] = $row[13];
  $node->field_notes[$lang][0]['value'] = $row[14];
  $node->field_airport[$lang][0]['value'] = $row[15];
  $node->field_instructors[$lang][0]['value'] = $row[17];

  # Prepare the node and save it
  $node = node_submit($node);
  node_save($node);

  $rec_count += 1;

}



### Summary printouts
print "Added $rec_count flight school records\n";
