<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: group-status-submit
// Purpose: To change group information
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
initializeScript("in");

$group_id=$_GET["group_id"];
if (isset($group_id) && $group_id != '') {
  if (isGroup($group_id) && isOwner($group_id, getUserID(currentUser()))) {
    $group_string = getGroup($group_id);
    $group_home_link = createLink("view-group?group_id=$group_id", "Return to Current Group's Home");
    $current_members = showUserStatus($group_id, true);
    $pending_members = showPendingUsers($group_id);
    $template_variables = array("group_link" => $group_home_link);
    $leftbar = evalTemplate("view-group-left-bar.template", $template_variables);   
    $leftbar .= groupListForMemberLeftBar(getUserId(currentUser()));
    $template_variables = compact("current_members", "pending_members", "group_string", "group_home_link");
    generatePage("group-status", "Group Membership", evalTemplate("group-status.template", $template_variables), $leftbar);
  } else {
    redirectHeader("home"); // Tried to hack a pointer.
  }
} else {
  redirectHeader("home"); // Tried to hack a pointer.
}