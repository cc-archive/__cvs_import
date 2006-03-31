<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: view-group
// Purpose: To view group information
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-content/content-interface.php");
initializeScript("either");

$group_id=$_GET["group_id"];
if (isset($group_id) && $group_id != '' && isGroup($group_id)) {
  $group_string = getGroup($group_id);
  $group_members = showUserStatus($group_id, false);
  if (isLoggedIn()) {
    $user_id = getUserID(currentUser());
    if (isMember($group_id, $user_id)) {
      $user_options = createLink("group-status-submit?group_id=$group_id&user_id=$user_id&status=leave", "Leave Group");
      $group_forum = getGroupForum($group_id);
      $forum = generateAllThreads($group_forum, "forums-specific-thread?view_forum=" . $group_forum->content_id);
      $template_variables = compact("forum");
      $forumstring = evalTemplate("view-group-group-forum.template", $template_variables);
    } else if (isPendingMember($group_id, $user_id)) {
      $user_options = createLink("group-status-submit?group_id=$group_id&user_id=$user_id&status=leave", 
				 "Cancel Membership Request");
    } else {
      $user_options = createLink("group-status-submit?group_id=$group_id&user_id=$user_id&status=join", "Join Group");
    }

    if (isOwner($group_id, getUserID(currentUser()))) {
      $owner_options = createLink("group-status?group_id=$group_id", "Edit Group Memberships For Current Group");
    }
  }
  if (isAdmin()) {
    $admin_options = "<BR><BR> Administrative Options: " . 
      createLink("group-delete-submit.php?group_id=$group_id", "Delete Group");
  }
  $template_variables = array("group_link" => $owner_options);
  if (isLoggedIn()) {
    $leftbar = evalTemplate("view-group-left-bar.template", $template_variables);   
    $leftbar .= groupListForMemberLeftBar(getUserId(currentUser()));
  }
  $template_variables = compact("group_string", "group_members", "user_options", "owner_options", "admin_options", "forumstring");
  generatePage("view-group", "View Group", evalTemplate("view-group.template", $template_variables), $leftbar);
} else {
  redirectHeader("home"); // They tried to hack a pointer somewhere;
}