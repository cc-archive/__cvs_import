<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: change-password
// Purpose: Lets a user change their password.

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("in");

$username = currentUser();

$template_variables = compact("username");

generatePage("change-password", "Change Password", evalTemplate("change-password.template", $template_variables));