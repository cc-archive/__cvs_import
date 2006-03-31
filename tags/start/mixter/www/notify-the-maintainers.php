<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: Log
// Purpose: View the general purpose site log
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");

// $cmd = "cat /var/log/apache2/error_log | grep " . $GLOBALS['server'] . " > /tmp/recentlog.log";
$cmd = "cat /var/log/apache2/error_log > /tmp/recentlog.log";
exec($cmd);
//echo $cmd . "<BR>";
$cmd = "diff /tmp/recentlog.log /tmp/lastlog.log > /tmp/diff.log";
exec($cmd);
//echo $cmd . "<BR>";

$diff_between_logs = file_get_contents("/tmp/diff.log");

echo "Note: This is a rather minimal page, since it isn't meant to be viewed really, and this is only for debugging. When Mixter goes live this page should not be in the web directory, and should get run as a cron job every fifteen minutes.<BR><BR>";

if ($diff_between_logs != '') {
  mail($GLOBALS['page_admin_email'],
       "Mixter Server Error Logs",
       $diff_between_logs,
       "From: " . $GLOBALS['page_admin_email'] . "\r\n" . "Reply-To: " . $adminemail . "\r\n" . "X-Mailer: PHP/" . phpversion());
  echo "Mail Was Just Sent To<BR><BR>";
  echo "To: " . $GLOBALS['page_admin_email'] . "<BR><BR>";
  echo "From: " . $GLOBALS['page_admin_email'] . "<BR><BR>";
  echo "Subject: Mixter Server Error Logs<BR><BR>";
  echo "Body: " . $diff_between_logs . "<BR><BR>";
  echo "X-Mailer: PHP/" . phpversion();
} else {
  echo "No changes - no email was sent";
}
echo "<BR>";
$cmd = "rm /tmp/lastlog.log";
exec($cmd);
//echo $cmd . "<BR>";
$cmd = "mv /tmp/recentlog.log /tmp/lastlog.log";
exec($cmd);
//echo $cmd;