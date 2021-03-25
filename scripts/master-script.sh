echo "************ Initialize Ruby environment ***********"
source ~webmaster/rails_env.sh

# Set environment variable for the Drupal home directory
export D7=/usr/local/share/drupal7

echo "************ Update Members ************"
cd ~/scripts/members; bash ./update-members.sh

echo; echo; echo "************ Delayed Job Restart ************"
cd ~www-data/iaccdb; ./script/delayed_job
sleep 30

echo; echo; echo "************ Update Judges ************"
cd ~/scripts/judges; bash ./daily-update.sh

echo; echo; echo "************ Judges School Nag ************"
/usr/local/bin/drush7 -r /usr/local/share/drupal7 -l www.iac.org scr /home/webmaster/scripts/judges-schools/nag-student-list.php
