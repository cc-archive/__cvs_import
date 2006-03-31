<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page:forgotpassword
// Purpose:If the user forgets their login name and password, they can request a new one be emailed to them.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("either");

generatePage("forgot-password", "Forgot Your Password?", evalTemplate("forgot-password.template", array()));

