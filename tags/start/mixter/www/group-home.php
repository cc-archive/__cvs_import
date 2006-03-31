<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: group-home
// Purpose: Acts as a collection of links for users to control their group membership settings.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$group_memberships = groupListForMember(getUserID(currentUser()));
$template_variables = compact("group_memberships");
generatePage("group-home", "Group Home", evalTemplate("group-home.template", $template_variables), groupListForMemberLeftBar(getUserID(currentUser())));