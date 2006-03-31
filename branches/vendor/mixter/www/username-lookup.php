<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page:usernamelookup
// Purpose:If the user forgets their login name, they can do a search by their email address.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("out");

generatePage("username-lookup", "Lookup Username", evalTemplate("username-lookup.template", array()));

?>

