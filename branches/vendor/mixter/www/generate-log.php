<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: generate-log
// Purpose: Generate the actual general purpose log
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");

initializeScript("in");

checkAdminStatus("admin");

exec("cat /var/log/apache2/access_log | grep " . $GLOBALS['server'] . " > /tmp/log.log");
system("/home/httpd/htdocs/mattroot/analog-5.32/analog");

?>