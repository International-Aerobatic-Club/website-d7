<?php

# DJM, 2016-06-02

# Read a list of bounced email addresses from STDIN, and
# mark the corresponding user records accordingly.

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr mark-bad-addrs.php < input-file

# Give ourselves site admin privileges
global $user;
$user = user_load(1);

while (TRUE) {

  # Read a line and discard the newline
  $mail = trim(fgets(STDIN));

  # Quit if there's we're out of data
  if (feof(STDIN)) { return; }

  # Get the corresponding user record
  $u = user_load_by_mail($mail);

  # Found user record?
  if ($u) {

    # Is the email addr marked as something other than bad? (nil or zero/false)
    if ($u->field_bad_email['und'][0]['value'] != "1") {

      # No, mark it bad
      $edit = array( 'field_bad_email' => array( 'und' => array( 0 => array( 'value' => "1") ) ) );

      # Save the changes
      if (!user_save($u, $edit)) {
        print "******** COULDN'T UPDATE RECORD FOR " . $u->name . "\n";
        exit(1);
      }

      print "** Updated " . $u->name . "\n";

    } else {
      print "** Already marked as bad: " . $u->name . "\n";
    }

  } else {
    print "** No corresponding user record for: " . $mail . "\n";
  }

}
