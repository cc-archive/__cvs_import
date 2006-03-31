<html>
<head>
<title>License Chooser Results</title>
</head>
<body>
<p>You chose:
<a href="<?php echo($_POST['license_uri']) ?>">
<?php echo($_POST['license_name']) ?>
</a>
</p>
<p>Including you license on a web page would look like this:</p>
<div>
  <?php echo($_POST['license_html']); ?>
</div>

</body>
</html>
