<?php

/* Blank template file */

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language; ?>" version="XHTML+RDFa 1.0" dir="<?php print $language->dir; ?>">
  <head>
    <base href='<?php print $url ?>' />
  </head>
  <body>
    <p> Here it is:
     <?php print_r($page); ?>
    </p>
  </body>
</html>
