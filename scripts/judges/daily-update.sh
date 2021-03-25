#!/bin/bash
export D7=/usr/local/share/drupal7
sudo -u www-data drush7 -r $D7 -l www.iac.org scr import.php `date '+%Y'`
sudo -u www-data drush7 -r $D7 -l www.iac.org scr currency.php
