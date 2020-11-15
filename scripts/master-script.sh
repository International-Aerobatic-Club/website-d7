echo "************ Initialize Ruby environment ***********"
source ~webmaster/iaccdb/rails_env.sh

echo "************ Update Members ************"
cd ~/scripts/members; bash ./update-members.sh

echo; echo; echo "************ Delayed Job Restart ************"
cd ~/iaccdb; bash -l ./shared/restart_delayed_job.sh
sleep 30

echo; echo; echo "************ Update Judges ************"
cd ~webmaster/scripts/judges; bash ./daily-update.sh

# echo; echo; echo "************ Judges School Nag ************"
# drush -r /usr/local/share/drupal7 -l www.iac.org scr /home/webmaster/scripts/judges-schools/nag-student-list.php

echo; echo; echo "************ Hacker Blocker ************"
drush -r /usr/local/share/drupal7 -l www.iac.org scr /home/webmaster/scripts/hacker-blocker/hacker-logins.php

