<?php

# Nag Judges School authors if they have not entered student names
# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr nags.php
# DJM, 2016-12-08

$query = new EntityFieldQuery();
$result = $query->entityCondition('entity_type', 'node')
  ->entityCondition('bundle', 'contest')
  ->propertyCondition('status', NODE_PUBLISHED)
  ->execute();

$contests = node_load_multiple(array_keys($result['node']));

/* Loop through the schools */
foreach($contests as $contest) {
// !!!!!!!!!!!!! STOPPED EDITING HERE !!!!!!!!!!!

  /* Don't nag if any students are recorded */
  if (count($school->field_students) > 0) { continue; }

  /* Skip the school if it's scheduled less than two days ago, including future schools */
  if ($school->field_dates_and_times_with_tz['und'][0]['value'] > (time() - (2 * 24 * 3600))) {
    continue;
  }

  /* Get the node author */
  $u = user_load($school->uid);

  /* Set the 'to' address to be the node author's email unless it's a bogus placeholder */
  $to = (preg_match('/@nomail\.iac\.org$/', $u->mail) == 0) ? $u->mail : '';

  /* Find the Judges Program Chair(s) */
  $result = db_query("
    SELECT users.mail FROM role, users, users_roles
      WHERE role.name = 'judges chair'
        AND role.rid = users_roles.rid
        AND users_roles.uid = users.uid
    ");

  /* Build the 'cc' list, filtering out bogus placeholder emails */
  $cc = array('webmaster@iac.org');
  foreach($result as $chair) {
    if (preg_match('/@nomail\.iac\.org/', $chair->mail) == 0) {
      $cc[] = $chair->mail;
    }
  }

  /* Get the Webmaster's user record */
  $webmaster = user_load(1);

  /* Build the nag email */
  /* drupal_mail($module, $key, $to, $language, $params = array(), $from = NULL, $send = TRUE) */
  $message = drupal_mail(
    'iac',
    'nag_judges_school_students',
    $to,
    user_preferred_language($u),
    array(),
    $webmaster->mail,
    FALSE
  );

  $message['cc'] = implode(',', $cc);
  $message['subject'] = "[IAC] Judges School -- missing student names";
  $message['body'] = 
    "Dear " .  $u->name .  ",\n\n" .
    "This is a friendly reminder to submit the student names from your recent Judges School. " .
    "This information is critical for both certification and recurrency.\n\n" .
    "To do so, please visit https://www.iac.org/node/" . $school->nid . "/edit (member login required). " .
    "Thanks for your help!";

  // Retrieve the responsible implementation for this message.
  $system = drupal_mail_system('iac', 'nag_judges_school_students');

  // Format the message body.
  // $message = $system->format($message);

  // Send the e-mail.
  $system->mail($message);

  print "SENT NAG MESSAGE:\n";
  print ($message['body']);
  print "\n\n";
}
