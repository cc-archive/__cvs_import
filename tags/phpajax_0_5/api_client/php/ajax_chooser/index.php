<?php

   require_once(dirname(__FILE__).'/cc_ajax/cc_ajax.php');

?>

<html>
  <head>
    <title>Creative Commons AJAX License Chooser Demo</title>

<!-- insert the Javascript include statements -->
<?php scriptHeader($base='.'); ?>

<style>
th { 
    white-space: nowrap; 
}

img {
    display:none; 
    float:right; 
    margin:0px;
    padding;0px;
}

</style>
  </head>

  <body>

<?php licenseChooser($action='choose.php', $prefix=''); ?>

  </body>
</html>
