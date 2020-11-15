<?php

// $_SERVER['HTTP_HOST'] = 'www.iac.org';

define('DRUPAL_ROOT', getenv('D7'));

require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_DATABASE);

print "after bootstrap!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!1\n";

?>
