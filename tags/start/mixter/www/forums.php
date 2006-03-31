<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forums
// Purpose: view forums
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("either");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/user-input.lib");

$forumlist = viewListOfForums(getListOfNonGroupForums(), "forums-specific-forum?");

if (isAdmin()) {
  $admin_interface = adminForumView($_GET['one_line_summary'], $_GET['description']);
} else {
  $admin_interface = '';
}
$template_variables = compact("forumlist", "admin_interface");
generatePage("forums", "Forums", evalTemplate("forums.template", $template_variables));
