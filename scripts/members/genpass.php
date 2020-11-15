<?php

// Password Generator.
// Selects four words at random from a dictionary file and combines them.
// DJM, 2012-08-13


	// Seed the random number generator
	srand(substr(microtime(),2,8) + time());

	// Array in which to store the words
	$pass_words = array();

	$pass_file = fopen("word-list.txt", "r");

	// Read the words from the dictionary file, one per line
	while (!feof($pass_file)) {
		$words[] = trim(fgets($pass_file));
	}

	// Array to store the password elements
	$pw = array();

	// Loop 4x
	for ($i = 0; $i < 4; $i++) {

		// Pick and store a new word
		$pw[] = $words[rand(0, count($words)-1)];
	}

	// Connect the words with dashes
	print(implode("-", $pw) . "\n");

?>
