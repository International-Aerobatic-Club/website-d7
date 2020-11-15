<?php

// This script syncs the EAA-supplied membership list with the Drupal User nodes
// DJM, 2015-10-05

// Note: Members who no longer appear in the EAA download are retained in our
// database for archival purposes. There is a cron job that revokes membership
// privileges when their membership expires. Therefore this script doesn't purge
// obsolete records.

// Note: This script depends on the diligent-drupal module, a collection of utility
//       functions. The function _dili_efv extracts a Drupal field value, and
//       _dili_sfv sets a field value. These do away with awkward constructs such
//       as: $p->field_city['und'][0]['value']

// Usage: drush -r $D7 -l www.iac.org scr syncit.php [--readonly] < membership-list.csv

define(IGNORE_EMAIL, '@nomail.iac.org');
define(NEW_USER_LIMIT, 25);

define(IAC_CURRENT_MEMBER_ROLE, 'current member');

if (end($_SERVER['argv']) == "--readonly") {
  $readonly = true;
  print "*** READ-ONLY MODE\n\n";
} else {
  $readonly = false;
}


// Retrieve the "current member" role record
if (($cr = user_role_load_by_name(IAC_CURRENT_MEMBER_ROLE)) == FALSE) {
  print("Can't find role: " . IAC_CURRENT_MEMBER_ROLE . "\n");
  exit(1);
}

// Save the role id
$current = intval($cr->rid);


// Array for user records
$csv = Array();

// Array of first/last name pairs. Needed for checks of uniqueness.
$name_count = Array();


// Normalize format of U.S. phone numbers
function fmt_phone($ph) {

  // Get rid of leading/trailing white space and newlines
  $tp = trim($ph);

  if (preg_match('/^[2-9][0-9][0-9]  [2-9][0-9][0-9]-?[0-9][0-9][0-9][0-9]$/', $tp)) {

    $clean = preg_replace('/\s/', '', str_replace('-', '', $tp));
    return '(' . substr($clean, 0, 3) . ') ' . substr($clean, 3, 3) . '-' . substr($clean, 6, 4);

  } else {
    return $tp;
  }

}


// Make a unique name of the form: John Smith 12345  (where 12345 is the IAC #)
function unique_name($nc, $first_last, $iac_number)
{
  /* Force case-insensitive lookup by converting to lower case */
  if ($nc[strtolower($first_last)] == 1) {
    return($first_last);
  } else {
    return ($first_last . ' ' . $iac_number);
  }
}


function gen_email($iacnum) {
  return($iacnum . IGNORE_EMAIL);
}


// Read (and ignore) the CSV file header
$a = fgetcsv(STDIN);

// Inhale the rest of the CSV file and store
// the fields in an array of associative arrays

// IACNum,ExpireDate,EAAMembeNumber,LastName,FirstName,Address1,Address2,City,State,Zip,Country,emailaddress,PhoneNumber,MemberType

while ($row = fgetcsv(STDIN)) {

  $csv[] = Array(
		'iac_number' => $row[0],
		'expire_date' => $row[1],
		'eaa_number' => $row[2],
		'last_name' => utf8_encode($row[3]),
		'first_name' => utf8_encode($row[4]),
		'address1' => utf8_encode($row[5]),
		'address2' => utf8_encode($row[6]),
		'city' => utf8_encode($row[7]),
		'state' => utf8_encode($row[8]),
		'zip' => $row[9],
		'country' => utf8_encode($row[10]),
		'email_address' => $row[11],
		'phone_number' => $row[12]
  );

  /*
   * Combine first & last names.
   * Force the result to lower case so that we don't end up with different entries in $name_count
   * for names that only differ in terms of capitalization.
   */
  $n = strtolower($row[4] . ' ' . $row[3]);

  if (isset($name_count[$n])) {
    $name_count[$n] += 1;
  } else {
    $name_count[$n] = 1;
  }

}


// Since we will need to look up users from our database using IAC number
// as the key, let's construct a map of IAC numbers to uids

// Grab iac_number/uid pairs by keying off the data field that contains IAC
// numbers, then join the profile table
// TODO: Use the Drupal API instead!

$result = db_query("
  SELECT field_iac_number_value AS iac_number, profile.uid
    FROM field_data_field_iac_number, profile
    WHERE field_iac_number_value > 0
      AND field_iac_number_value < 99990000
      AND field_data_field_iac_number.entity_type = 'profile2'
      AND field_data_field_iac_number.entity_id = profile.pid;
");

// Array for storing the iac_number-to-uid associations
$map = Array();

// Build the map as a PHP associative array
foreach ($result as $rec) { $map[$rec->iac_number] = $rec->uid; }



// Look for any users in our database who are NOT present in the EAA file

foreach (array_flip($map) as $iacnum) {

  $found = 0;

  foreach ($csv as $row) {
    if ($row['iac_number'] == $iacnum) {
      $found = 1;
      break;
    }
  }

  if ($found == 0) {

    $u = user_load($map[$iacnum]);
    $p = profile2_load_by_user($u, 'main');

    if (_dili_efv($p->field_hidden) == 0) { 
      print("*** Member " . $u->name . " (" . $iacnum . ") not in download - marking record as 'hidden'\n");
      $p->field_hidden = _dili_sfv(1);
      if (!$readonly) { profile2_save($p); }
    }

  }
}


$new_users = 0;

print "** Checking for changes to member info\n";

// Run through the EAA list of members
foreach ($csv as $row) {

  // Convenience var
  $iacnum = $row['iac_number'];

  // See if this EAA-supplied IAC number is in our DB
  if (isset($map[$iacnum])) {

    // Yup. Now compare fields to see if anything needs updating.

    /* Array of strings, listing any profile fields that are changed */
    $profile_changes = Array();

    // Get the user record and profile info
    $u = user_load($map[$iacnum]);
    $p = profile2_load_by_user($u, 'main');

    if (date_parse($row['expire_date']) != date_parse(_dili_efv($p->field_member_thru))) {

      $d = date_parse($row['expire_date']);
      $t = mktime(0, 0, 0, $d['month'], $d['day'], $d['year']);
      $p->field_member_thru = _dili_sfv(date("Y-m-d 00:00:00", $t));
      $p->field_hidden = _dili_sfv(0);

      $profile_changes[] = 'Expiration Date';

    }

    $fl = $row['first_name'] . ' ' . $row['last_name'];
    $un = unique_name($name_count, $fl, _dili_efv($p->field_iac_number));
    if ($u->name != $un) {
      $u->name = $un;
      if (!$readonly) { 
        try {
          /* Attempt to save the user although, if there are issues w the upstream EAA
             database (multiple entries for a single user), we catch the exception and
             dump to collect the error and ensure we continue processing the rest of the
             users */
          user_save($u);
        } catch(Exception $e) {
          print("Exception adding user " . $u->name . ":\n");
          print($e . "\n");
        }
      }
      print("Updated member name for " . $u->name . "\n");
    }


    // Update EAA # as needed
    if ($row['eaa_number'] != _dili_efv($p->field_eaa_number)) {
      $p->field_eaa_number = _dili_sfv($row['eaa_number']);
      $profile_changes[] = 'EAA Number';
    }

    // Update mailing address as needed
    if ($row['address1'] != _dili_efv($p->field_street_address)) {
      $p->field_street_address = _dili_sfv($row['address1']);
      $profile_changes[] = 'Street Address';
    }

    if ($row['address2'] != _dili_efv($p->field_address2)) {
      $p->field_address2 = _dili_sfv($row['address2']);
      $profile_changes[] = 'Address2';
    }

    if ($row['city'] != _dili_efv($p->field_city)) {
      $p->field_city = _dili_sfv($row['city']);
      $profile_changes[] = 'City';
    }

    if ($row['state'] != _dili_efv($p->field_state)) {
      $p->field_state = _dili_sfv($row['state']);
      $profile_changes[] = 'State';
    }

    if ($row['zip'] != _dili_efv($p->field_zip_code)) {
      $p->field_zip_code = _dili_sfv($row['zip']);
      $profile_changes[] = 'Zip';
    }

    if ($row['country'] != _dili_efv($p->field_country)) {
      $p->field_country = _dili_sfv($row['country']);
      $profile_changes[] = 'Country';
    }


    // Sync the phone number
    $ph = fmt_phone($row['phone_number']);

    if ($ph != _dili_efv($p->field_phone)) {
      $p->field_phone = _dili_sfv($ph);
      $profile_changes[] = 'Phone Number';
    }


    // If EAA has an email address on file and it differs from
    // the one in our database, use EAA's value and mark it as valid.
    if (!empty($row['email_address']) && $row['email_address'] != $u->mail) {
      $u->mail = $row['email_address'];
      $u->field_bad_email = FALSE;
      if (!$readonly) { user_save($u); }
      print("Updated email address for " . $u->name . "\n");
    }

    // Un-hide the user if necessary
    if (_dili_efv($p->field_hidden) == 1) {
      $p->field_hidden = _dili_sfv(0);
      $profile_changes[] = 'Hidden Status';
    }


    // Do all profile changes in one fell swoop
    if (sizeof($profile_changes) > 0) {
      if (!$readonly) { profile2_save($p); }
      print ("For $u->name, changed profile fields: " . implode(', ', $profile_changes) . "\n");
    }


  // Else we haven't seen this IAC number before, so generate a new user

  } elseif ($new_users < NEW_USER_LIMIT) {

    // Build a fake email address if none is provided
    if (empty($row['email_address'])) {
      $m = gen_email($iacnum);
    } else {

      // Save the email address from the CSV record
      $m = $row['email_address'];

      // Is this email address already in use?
      if (db_query("SELECT uid FROM users WHERE mail = :mail;", array(':mail' => $m))->rowCount() > 0) {
        print("****** DUPLICATE: Email address $m is already in use!\n");
        continue;
      }

    }

    // Build user record
    $newUser = array(
      'name' => unique_name($name_count, $row['first_name'] . ' ' . $row['last_name'], $row['iac_number']),
      'mail' => $m, 'status' => 1, 'init' => $m
    );            

    $newUser[roles] = array(
      DRUPAL_AUTHENTICATED_RID => 'authenticated user',
      $current => IAC_CURRENT_MEMBER_ROLE,
    );

    if (!$readonly) {

      // Save the user
      $u = user_save(null, $newUser);

      // Make sure it succeeded
      if ($u == FALSE) {
        print("Failure when saving user!\n");
        var_dump($newUser);
        exit(1);
      }

    }


    // create profile object
    $p = profile_create(array('user' => $u, 'type' => 'main'));

    // Populate profile fields:
    //   IACNum, ExpireDate, EAAMembeNumber, LastName, FirstName,
    //   Address1, Address2, city, state, zip, country, emailaddress, PhoneNumber

    // IAC number
    $p->field_iac_number = _dili_sfv($row['iac_number']);

    // Member Thru
    $d = date_parse($row['expire_date']);
    $t = mktime(0, 0, 0, $d['month'], $d['day'], $d['year']);
    $p->field_member_thru = _dili_sfv(date("Y-m-d 00:00:00", $t));

    // Address Line 1, if present
    if (!empty($row['address1'])) {
      $p->field_street_address = _dili_sfv($row['address1']);
    }

    // Address Line 2, if present
    if (!empty($row['address2'])) {
      $p->field_address2 = _dili_sfv($row['address2']);
    }

    // City, if present
    if (!empty($row['city'])) {
      $p->field_city = _dili_sfv($row['city']);
    }

    // State, if present
    if (!empty($row['state'])) {
      $p->field_state = _dili_sfv($row['state']);
    }

    // Zip code, if present
    if (!empty($row['zip'])) {
      $p->field_zip_code = _dili_sfv($row['zip']);
    }

    // Country, if present
    if (!empty($row['country'])) {
      $p->field_country = _dili_sfv($row['country']);
    }

    // EAA #, if present
    if (!empty($row['eaa_number'])) {
      $p->field_eaa_number = _dili_sfv($row['eaa_number']);
    }

    if (!empty($row['phone_number'])) {
      $p->field_phone = _dili_sfv(fmt_phone($row['phone_number']));
    }

    // save profile
    if (!$readonly) { profile2_save($p); }

    print("Created new user " . $newUser['name'] . "\n");

    $new_users++;

  }  # New user

} # For each CSV row

# Output final tally
print("\n\n*** Created a total of " . $new_users . " new users.\n");


?>
