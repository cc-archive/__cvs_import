<?php include('../head.inc'); ?>

<h1>Add a URL</h1>
<?php

$url = $_REQUEST[url];

if ($url) {
  if (!$fp = fopen('/var/log/user_url_submissions', 'a')) {
    print "Cannot open file";
    exit;
  }
  if (!fwrite($fp, "{$url}\n")) {
    print "Cannot write to file";
    exit;
  }
  echo("<p>Thank you for submitting <a href=\"{$url}\">{$url}</a>.  It will be indexed in the next several days.  Please <a href=\"http://www.yergler.net/projects/ccvalidator/validate.py?url={$url}\">validate {$url}</a> and add or fix its metadata as required (pages with missing or invalid metadata will not be indexed).  Proper metadata may be obtained via the Creative Commons <a href=\"http://creativecommons.org/license/\">licensing process</a>.</p>");
} else {
?>
<p>Enter the URL you wish to submit for indexing below.</p>
<form action="./" class="searchform">
<input type="text" name="url" size="40" />
<input type="submit" value="Submit URL" />
</form>
<?php
}
?>
<?php include('../foot.inc'); ?>
