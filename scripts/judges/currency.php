<?php

/* !!! Comments marked with three exclamation points implement the 2020 Board decisions
 *     re: judge currency amid the pandemic
*/

# DJM, 2017-03-09

# Calculate judge currency and update user records as appropriate,
# in accordance with the 2017 IAC Rule Book, Section 2.6.3

# Usage: sudo -u www-data drush -r $D7 -l www.iac.org scr currency.php [v]

global $verbose;
$verbose = FALSE;

# Process the command line arguments
if (sizeof($args) > 1 && $args[1] == "v") { $verbose = TRUE; }

function vprint($s) {
  global $verbose;
  if ($verbose) { print $s; }
}


# Give ourselves site admin privileges
global $user;
$user = user_load(1);

$current_year = intval(date('Y'));
$prev_year = $current_year - 1;
$prev_year2 = $current_year - 2;
$prev_year3 = $current_year - 3;

$prev_year3_ts = strtotime("$prev_year3-01-01");


# Titles of each year's R&C exam. Will need to update this annually.
# TODO: Come up with a more clever way of finding them. Using tags, perhaps?
$rc_exams = Array(
  2015 => '2015 Judges Revalidation & Currency Exam',
  2016 => '2016 Judge Revalidation & Currency Exam',
  2017 => '2017 JUDGE REVALIDATION & CURRENCY EXAM',
  2018 => '2018 Judges Revalidation & Currency Exam',
  2019 => '2019 Judges Revalidation & Currency Exam',
  2020 => '2020 Judges Revalidation & Currency Exam',
);




# Assoc. array of judging experiences, one element per user
$judges = Array();





######################### Get the list of non-current judges

# What about current judges, you ask? Once current, a judge stays that way for 
# the entire year. Therefore there is no need to re-evaluate their qualifications

$ncj_query = new EntityFieldQuery();
$ncj_results = $ncj_query->entityCondition('entity_type', 'profile2', '=')
  ->entityCondition('bundle', 'main', '=')
  ->fieldCondition('field_judge', 'value', 'No', '!=')
  ->fieldCondition('field_current', 'value', '1', '!=')
  ->execute();

if (empty($ncj_results['profile2'])) {
  print "*** No non-current judges!? Exiting.\n";
  return;
}

$ncjs = entity_load('profile2', array_keys($ncj_results['profile2']));

# Collect the uid fields from the user profiles
$uids = Array();
foreach($ncjs as $ncj) { $uids[] = $ncj->uid; }




######################### Add current Regional-N judges to the array of non-current judge IDs
# This is needed because if a Regional-N judge attends a school *after* taking the R&C exam,
# their status should change from Regional-N back to National. (A corner case, but it could happen.)

$rnj_query = new EntityFieldQuery();

$rnj_results = $rnj_query->entityCondition('entity_type', 'profile2', '=')
  ->entityCondition('bundle', 'main', '=')
  ->fieldCondition('field_judge', 'value', 'Regional-N', '=')
  ->fieldCondition('field_current', 'value', '1', '=')
  ->execute();

if (!empty($rnj_results['profile2'])) {
  $rnjs = entity_load('profile2', array_keys($rnj_results['profile2']));
  foreach($rnjs as $rnj) { $uids[] = $rnj->uid; }
}





######################### Initialize an associative array for each judge

foreach($uids as $uid) {
  $judges[$uid] = Array();
  $judges[$uid]['total'] = 0;
  $judges[$uid]['adv_unl_free'] = 0;
  $judges[$uid]['rc_pass'] = FALSE;
  $judges[$uid]['school_year'] = 0;
  $judges[$uid]['judged_nats'] = FALSE;
}





######################### Get the list of contests from the previous year

$contest_query = new EntityFieldQuery();
$contest_results = $contest_query->entityCondition('entity_type', 'node', '=')
  ->entityCondition('bundle', 'contest', '=')
  ->propertyCondition('status', NODE_PUBLISHED)
  ->fieldCondition('field_contest_dates', 'value', "$prev_year-01-01", '>=')
  ->fieldCondition('field_contest_dates', 'value', "$prev_year-12-31", '<=')
  ->execute();

$cids = array_keys($contest_results['node']);






##################### Get the previous year's Line Judge & Chief Judge
##################### judging_experience records for the non-current judges

$je_query = new EntityFieldQuery();
$je_results = $je_query->entityCondition('entity_type', 'node', '=')
  ->entityCondition('bundle', 'judging_experience', '=')
  ->propertyCondition('status', NODE_PUBLISHED)
  ->fieldCondition('field_member', 'target_id', $uids, 'IN')
  ->fieldCondition('field_contest', 'target_id', $cids, 'IN')
  ->fieldCondition('field_role', 'value', array('Line Judge', 'Chief Judge'), 'IN')
  ->execute();

# Load the nodes returned by the query
if (empty($je_results['node'])) {
  print "*** No judging experience records so far this year.\n";
  $judging_experiences = Array();
} else {
  print '*** Loading ' . sizeof($je_results['node']) . ' judging experience records... ';
  $judging_experiences = node_load_multiple(array_keys($je_results['node']));
  print "done.\n";
}


# Tote up the judging experience
foreach($judging_experiences as $je) {

  $uid = $je->field_member['und'][0]['target_id'];

  # Add in the total flights judged
  $judges[$uid]['total'] += $je->field_number_of_flights['und'][0]['value'];

  # If category is Advanced or Unlimited (Power or Glider) and flight is Free,
  # add those flights to a separate total.
  if (((strrpos($je->field_category['und'][0]['value'] === 0, 'Advanced') == 0) ||
       (strrpos($je->field_category['und'][0]['value'] === 0, 'Unlimited') == 0)) &&
       $je->field_flight['und'][0]['value'] == 'Free') {
    $judges[$uid]['adv_unl_free'] += $je->field_number_of_flights['und'][0]['value'];
  }

  # If the contest is Nationals, mark the user record accordingly.
  if ($je->field_nationals['und'][0]['value'] == '1') { $judges[$uid]['judged_nats'] = TRUE; }

}






##################### See who passed the R&C

# Locate this year's R&C Exam node
if (empty($rc_exams[$current_year])) {
  print "No title available for this year's R&C exam page! Update ~webmaster/scripts/judges/currency.php\n";
  return;
}


$exam_query = new EntityFieldQuery();

$exam_results = $exam_query->entityCondition('entity_type', 'node')
  ->propertyCondition('type', 'quiz')
  ->propertyCondition('title', $rc_exams[$current_year])
  ->propertyCondition('status', NODE_PUBLISHED)
  ->propertyCondition('status', 1)
  ->execute();

if (empty($exam_results['node'])) {
  print "Can't locate Quiz node: $rc_exams[$current_year].\n";
  return;
}

# Load the current R&C exam node
$rc_exam = node_load(array_shift(array_keys($exam_results['node'])));

# Collect all vid values for this R&C exam, because User A may pass the exam, then
# the exam changes creating a new vid, then User B passes the exam. And the Quiz
# module's quiz_is_passed method only takes a scalar vid parameter. (eye roll)

$rc_vids = Array();
foreach(node_revision_list($rc_exam) as $rev) { $rc_vids[] = $rev->vid; }




# See if each judge has passed this year's R&C exam
foreach($judges as $jid=>$judge) {
  foreach ($rc_vids as $vid) {
    if (quiz_is_passed($jid, $rc_exam->nid, $vid)) {
      $judges[$jid]['rc_pass'] = TRUE;
      break;
    }
  }
}




################## Get all Judges School records from the previous three calendar years

$school_query = new EntityFieldQuery();

$school_results = $school_query->entityCondition('entity_type', 'node')
  ->propertyCondition('type', 'judges_school')
  ->propertyCondition('status', NODE_PUBLISHED)
  ->fieldCondition('field_dates_and_times_with_tz', 'value', $prev_year3_ts, '>=')
  ->fieldCondition('field_school_type', 'value', array('Intro', 'Session2', 'Adv'), 'IN')
  ->fieldOrderBy('field_dates_and_times_with_tz', 'value')
  ->execute();

if (empty($school_results['node'])) {
  print "No schools in the past three years!? Something's wrong, exiting.\n";
  return;
}

$schools = node_load_multiple(array_keys($school_results['node']));


# Use the 'iac' module to extract the list of students
$students = iac_judges_school_attendees();

# Loop through student
foreach($students as $uid => $school_year) {
  if (isset($judges[$uid])) {
    $judges[$uid]['school_year'] = $school_year;
  }
}





######################### Finally, loop through the judges and see if any are now current

$cur_count = 0;

foreach($judges as $jid=>$judge) {

  $u = user_load($jid);
  vprint("\n$u->name: \n");

  // Only members can be judges, per 2.6(a)
  vprint('  Current member: ');
  if (array_search('current member', $u->roles)) {
    vprint("Yes\n");
  } else {
    vprint("No\n");
    continue;
  }

  vprint('  Flights: ' . $judge['total'] . ' total, ' . $judge['adv_unl_free'] . " Adv/Unl Free\n");
  vprint('  Last judges school: ' . $judge['school_year'] . "\n");
  
  // Every judge has to pass the R&C every year, so make this the first test
  vprint('  Passed R&C: ');
  if ($judge['rc_pass']) {
    vprint("Yes\n");
  } else {
    vprint("No\n");
    continue; // Skip to next record
  }

  // Anyone who has judged 25 total flights, or 30 flights of which 5 are ADV or UNL,
  // or who has attended a judge's school in the past two years is current judge of some sort.

  // !!! IAC P&P 214 section 6.1 sub-paragraph b) i (required flights) be suspended* for 2021 Judge currency (requirement for number of flights Chief-ed or graded
  if ( /* !!! $judge['total'] >= 25 ||
    ($judge['total'] >= 20 && $judge['adv_unl_free'] >= 5) || */
  // !!! IAC P&P 214 section 6.1 sub-paragraph b) ii requirement for Regional Judges to attend a Judges School within 2 years be extended to 3 years for 2021 Judge Currency
    $judge['school_year'] >= /* !!! $prev_year2 */ $prev_year3) {

      // Increment the count
      $cur_count += 1;

      // Report the achievement
      $intro = '  Marking ' . $u->name . ' current as: ';

      # Get the member's profile record
      $profile = profile2_load_by_user($u->uid);

      # Extract the judge type
      $judge_type = $profile['main']->field_judge['und'][0]['value'];

      // Regionals stay regional
      if ($judge_type == 'Regional') {
        print $intro . "Regional\n";
      // Judge type must be National or Regional-N
      } elseif ($judge['school_year'] >= $prev_year3 || $judge['judged_nats']) {
        print $intro . "National\n";
        $profile['main']->field_judge['und'][0]['value'] = 'National';
      } elseif ($profile['main']->field_current['und'][0]['value'] != '1') {

        // Failure to attend school within the past 3 years and not having judged at Nationals
        // means that a National judge becomes Regional-N, or a Regional-N judge stays as Regional-N.
        print $intro . "Regional-N\n";
        $profile['main']->field_judge['und'][0]['value'] = 'Regional-N';
      } else {
        $cur_count -= 1;  # Existing Regional-N; decrement count
      }


      # Mark the judge as current
      $profile['main']->field_current['und'][0]['value'] = '1';

      # Save the changes
      profile2_save($profile['main']);

  } else {
    vprint("  Current: No\n");
  }

}

print "*** Marked " . $cur_count . " judges as current\n";
