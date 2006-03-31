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

initializeScript("in");

checkAdminStatus("admin");

exec("cat /var/log/apache2/error_log | grep " . $GLOBALS['server'] . " > /tmp/error.log");
$error_log = file_get_contents("/tmp/error.log");

$errors = explode("\n", $error_log);

$errors = array_reverse($errors);

$record_set = 0;
if ($_GET['record_set'] > 0)
  $record_set = $_GET['record_set'];

$output = array_slice($errors, $record_set*500, 500);

$page_data = implode("<BR><BR>\n", $output);

$record_start = $record_set * 500;
$record_end = $record_start + 499;
$set_minus = $record_set - 1;
$set_plus = $record_set + 1;
$prev = createLink("error-log?record_set=" . $set_minus, "Previous");
$next = createLink("error-log?record_set=" . $set_plus, "Next");

$template_variables = compact("record_start", "record_end", "prev", "next", "page_data");
generatePage("error-log", "Error Log", evalTemplate("error-log.template", $template_variables));
