#!/bin/bash
export D7=/usr/local/share/drupal7
drush -r $D7 -l www.iac.org scr import.php `date '+%Y'`
drush -r $D7 -l www.iac.org scr currency.php
