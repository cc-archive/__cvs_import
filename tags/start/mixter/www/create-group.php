<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page:create-group
// Purpose:Allow a user to create a new group.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("in");

$group_name = $_GET['groupname'];
$group_description = $_GET['groupdescription'];
$template_variables = compact("group_name", "group_description");

generatePage("create-group", "Create New Group", evalTemplate("create-group.template", $template_variables));