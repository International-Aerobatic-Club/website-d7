<?php

// This script blocks IP addresses of anyone who has attempted to login using a login ID
// that is known to be a target of hackers.
// DJM, 2016-03-04

# Usage: drush -r $D7 -l www.iac.org scr hacker-logins.php


define('DRUPAL_ROOT', getenv('D7'));
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_DATABASE);


function known_hacker($id) {

  $bad_accts = Array(
    'admin',
    'administrator',
    'www.iac',
    'miriam@f.coloncleanse.club',
    'knjfcgolxj',
    'littleshea20@yahoo.com',
    'sxxdenny@yahoo.com',
    'udaseegude',
    'uragojao',
    'uraqqemeluqi',
    'uvexpoqa',
    'vbxddfgyyl',
    'wpbarnar',
    'zavppdkjbp',
    'pletcherlcb',
    'puysmgucdc',
    'ojavumake',
    'ojayont',
    'otipeqen',
    'ecufaiiwec',
    'efokegovop',
    'ejupicveeros',
    'icazuhlpst',
    'igoacoogug',
    'iidahibemes',
    'inariuu',
    'adipex',
    'akayirobag',
    'amirute',
    'anohamenaze',
    'araguxod',
    'ardmxursl',
    'aritejihom',
    'aspymnbxiea5',
    'aspymncjorj8',
   );

  $bad_domains = Array(
    '1email.space',
    'discardmail.com',
  );

  if (array_search($id, $bad_accts) !== FALSE) { return TRUE; }

  $parts = explode('@', $id);
  $domain = $parts[1];
  if (array_search($domain, $bad_domains) !== FALSE) { return TRUE; }

  return FALSE;

}



$other_accts = Array();
$ips = Array();


$blocked_ips = Array();
$results = db_query("SELECT ip FROM blocked_ips");
foreach($results as $result) { $blocked_ips[] = $result->ip; }

// Get all failed logins in the past 25 hours (scripts runs once every 24, so this gives a little overlap)
$result = db_query("
  SELECT variables, hostname
    FROM watchdog
      WHERE message LIKE 'Login attempt failed%'
      AND timestamp > (UNIX_TIMESTAMP() - 90000)
");

foreach($result as $fields) {

  $v = unserialize($fields->variables);
  $u = strtolower($v['%user']);
  $ip = $fields->hostname;

  if (known_hacker($u)) {
    if (array_search($ip, $blocked_ips) === FALSE && filter_var($ip, FILTER_VALIDATE_IP)) {
      $ips[] = $fields->hostname;
    }
  } else {
    $other_accts[] = "$u";
  }
}


$uips = array_unique($ips);
sort($uips);
print "--- IPs to block:\n";
print implode("\n", $uips);

print "\n\n--- Non-target accts:\n";
sort($other_accts);
print implode("\n", array_unique($other_accts));

foreach($uips as $ip) {
  db_insert('blocked_ips')->fields(array('ip' => $ip))->execute();
}

print "\n\n--- Detected " . sizeof($ips) . " total attacks. Blocked " . sizeof($uips) . " distinct IP addresses.\n";
print "Size of blocked_ips list: " . (sizeof($blocked_ips) + sizeof($uips)) . "\n";
