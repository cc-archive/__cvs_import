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

$template_variables = compact();
generatePage("log", "Site Wide Log", evalTemplate("log.template", $template_variables));