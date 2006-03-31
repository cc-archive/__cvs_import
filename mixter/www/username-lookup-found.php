<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: username-lookup-found
// Purpose: Returns the found username
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("out");

require_once("../modules/mod-users/users-interface.php");

$template_variables = array("username" => $_GET['username'], "login_table" => createLoginForm($_GET['username']));

generatePage("username-lookup-found", "Username Found", evalTemplate("username-lookup-found.template", $template_variables));

?> 