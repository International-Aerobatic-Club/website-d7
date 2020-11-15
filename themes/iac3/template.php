<?php

/**
 * @file
 * Template.php - process theme data for your sub-theme.
 * 
 * Rename each function and instance of "footheme" to match
 * your subthemes name, e.g. if you name your theme "footheme" then the function
 * name will be "footheme_preprocess_hook". Tip - you can search/replace
 * on "footheme".
 */


/**
 * Override or insert variables for the html template.
 */
/* -- Delete this line if you want to use this function
function footheme_preprocess_html(&$vars) {
}
function footheme_process_html(&$vars) {
}
// */


/**
 * Override or insert variables for the page templates.
 */
/* -- Delete this line if you want to use these functions
function footheme_preprocess_page(&$vars) {
}
function footheme_process_page(&$vars) {
}
// */


/**
 * Override or insert variables into the node templates.
 */
/* -- Delete this line if you want to use these functions
function footheme_preprocess_node(&$vars) {
}
function footheme_process_node(&$vars) {
}
// */


/**
 * Override or insert variables into the comment templates.
 */
/* -- Delete this line if you want to use these functions
function footheme_preprocess_comment(&$vars) {
}
function footheme_process_comment(&$vars) {
}
// */


/**
 * Override or insert variables into the block templates.
 */
/* -- Delete this line if you want to use these functions
function footheme_preprocess_block(&$vars) {
}
function footheme_process_block(&$vars) {
}
// */


// Custom theme for International Aerobatic Club web site, www.iac.org.
// Based on Footheme by Adaptivethemes.com, a starter sub-sub-theme.
// DJM, 2015-01-18

function iac3_preprocess_html(&$vars) {

  // Load the media queries styles
  // If you change the names of these files they must match here - these files are
  // in the /css/ directory of your subtheme - the names must be identical!
  // $media_queries_css = array(
  //   'iac.responsive.style.css',
  //  'iac.responsive.gpanels.css'
  // );
  // load_subtheme_media_queries($media_queries_css, 'iac'); 

 /**
  * Load IE specific stylesheets
  * AT automates adding IE stylesheets, simply add to the array using
  * the conditional comment as the key and the stylesheet name as the value.
  *
  * See our online help: http://adaptivethemes.com/documentation/working-with-internet-explorer
  *
  * For example to add a stylesheet for IE8 only use:
  *
  *  'IE 8' => 'ie-8.css',
  *
  * Your IE CSS file must be in the /css/ directory in your subtheme.
  */
  /* -- Delete this line to add a conditional stylesheet for IE 7 or less.
  $ie_files = array(
    'lte IE 7' => 'ie-lte-7.css',
  );
  load_subtheme_ie_styles($ie_files, 'footheme'); // Replace 'footheme' with your themes name
  // */

}


/* Customize the user login block */
function iac3_form_user_login_block_alter(&$form, &$form_state, $form_id) {

	/* Change sizes of the login & password text boxes */
  $form['name']['#size'] = 20;
  $form['pass']['#size'] = 12;

	/* Change text of various labels */
	$form['name']['#title'] = "Email";
	$form['actions']['submit']['#value'] = "Go";
	$form['links']['#markup'] = str_replace("Request new password", "Lost password?", $form['links']['#markup']);

	/* Make the "Lost password?" link appear last */
	$form['actions']['#weight'] = 5;
	$form['links']['#weight'] = 10;

}



/* Customize the Search dialog */
function iac3_form_search_block_form_alter(&$form, &$form_state, $form_id) {

	global $user;

	/* Turn off autocomplete, coz it screws up the background colors */
	$form['#attributes']['autocomplete'] = 'off';

	/* Callback to insert JS for background prompts */
	$form['#after_build'] = array('_iac3_insert_prompts_js');

}


/* Insert the JavaScript that will diddle the background prompts */

function _iac3_insert_prompts_js($element) {
/* 	drupal_add_js(drupal_get_path('theme', 'iac') . '/js/jquery.infieldlabel.min.js');
 	drupal_add_js(drupal_get_path('theme', 'iac') . '/js/pop-labels.js'); */
	return($element);
}




/*
	Make the user's email field read-only,
	and remove the 'field required' asterisk for good measure
*/

function iac3_form_user_profile_form_alter(&$form, &$form_state) {
	$form['account']['mail']['#required'] = FALSE;
  $form['account']['mail']['#disabled'] = TRUE;
}



/*
	Make certain profile2 fields read-only,
	and remove the 'field required' asterisks for good measure
*/
function iac3_form_profile2_form_alter(&$form, &$form_state) {

  global $user;

	$form['profile_main']['field_iac3_number']['und']['0']['#disabled'] = TRUE;
	$form['profile_main']['field_iac3_number']['und'][0]['value']['#required'] = FALSE;

 	$form['profile_main']['field_street_address']['und']['0']['#disabled'] = TRUE;
 	$form['profile_main']['field_address2']['und']['0']['#disabled'] = TRUE;
 	$form['profile_main']['field_city']['und']['0']['#disabled'] = TRUE;
 	$form['profile_main']['field_state']['und']['0']['#disabled'] = TRUE;
	$form['profile_main']['field_zip_code']['und']['0']['#disabled'] = TRUE;

 	$form['profile_main']['field_phone']['und']['0']['#disabled'] = TRUE;

  if (!in_array('judges chair', $user->roles)) {
	  $form['profile_main']['field_judge']['und']['#attributes']['disabled'] = TRUE;
    $form['profile_main']['field_current']['und']['#attributes']['disabled'] = TRUE;
  }


	$form['profile_main']['field_member_thru']['und']['0']['#disabled'] = TRUE;

}




/* Hide the IACCDB ID field from all but Admin & Editors */
function iac3_form_contest_node_form_alter(&$form, &$form_state) {

  global $user;

  if (!_dili_user_has_role(array('administrator', 'editor'))) {
    $form['field_iaccdb_id']['und']['0']['#access'] = FALSE;
    $form['field_sanctioned']['und']['0']['#disbled'] = TRUE;
  }

}


/* Add premium content icon to links *
function iac3_link($variables) {

	global $user;
	$path = drupal_get_normal_path($variables['path']);

	if (substr($path, 0, 6) != 'admin/' &&
		  substr($path, 0, 7) != 'http://' &&
			substr($path, 0, 8) != 'https://') {

		$fh = fopen("/tmp/variables", 'a');
		fwrite($fh, '*** ' . date("c") . "\n");
		fwrite($fh, "Path='$path', Normal path='" . drupal_get_normal_path($path) . "'\n\n");
		fclose($fh);

	}

	return '<a href="' . check_plain(url($path, $variables['options'])) . '"' .
		drupal_attributes($variables['options']['attributes']) . '>' .
		($variables['options']['html'] ? $variables['text'] : check_plain($variables['text'])) . '</a>';

}


/* Add Google Analytics JS to the vendor_info view */
function iac3_preprocess_views_view(&$vars) {
   $view = $vars['view'];
   // Make sure it's the correct view
  if($view->name == 'vendor_info') {
     // add needed javascript
     drupal_add_js(drupal_get_path('theme', 'iac') . '/js/ga-event.js');
  }
}
