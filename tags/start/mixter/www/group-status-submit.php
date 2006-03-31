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
$user_id=$_GET["user_id"];
$status=$_GET["status"];
if (isset($group_id) && 
    isset($user_id) && 
    isset($status) && 
    $group_id != '' && 
    $user_id != '' && 
    $status != '' &&
    isGroup($group_id) &&
    ($status == 'leave' || $status == 'join' || $status == 'become_owner' || $status == "become_member")) {

  $current_user_id = getUserID(currentUser());

  if ($current_user_id == $user_id) {
    if ($status == "join" && !isMember($group_id, $current_user_id)) {
      addPendingMember($group_id, $current_user_id);
      $args = "?&group_id=$group_id&message=group-status-you-join";
      if (!usePageRedirect($_SERVER['HTTP_REFERER'] . $args))
        redirectHeader("home$args");
    } else if ($status == "leave" && (isMember($group_id, $current_user_id) || isPendingMember($group_id, $current_user_id))) {
      removeMember($group_id, $current_user_id);
      $args = "?&group_id=$group_id&message=group-status-you-leave";
      if (!usePageRedirect($_SERVER['HTTP_REFERER'] . $args))
        redirectHeader("home$args");
    } else if ($status == "become_member" && isOwner($group_id, $current_user_id)) { 
      setUsersGroupStatus($group_id, $user_id, "user");
      $args = "?&group_id=$group_id&message=group-status-you-demote";
      if (!usePageRedirect("view-group?group_id=$group_id"))
        redirectHeader("home$args");
    } else {
      redirectHeader("home"); // They tried to hack a pointer;
    }
  } else if (isOwner($group_id, $current_user_id)) {
    if ($status == "become_member" && (isMember($group_id, $user_id) || isPendingMember($group_id, $user_id))) {
      setUsersGroupStatus($group_id, $user_id, "user");
      $args = "?&group_id=$group_id&message=group-status-member-added";
      if (!usePageRedirect($_SERVER['HTTP_REFERER'] . $args))
        redirectHeader("home$args");
    } else if ($status == "leave" && !isOwner($group_id, $user_id) &&
	       (isMember($group_id, $user_id) || isPendingMember($group_id, $user_id))) {
       removeMember($group_id, $user_id);
       $args = "?&group_id=$group_id&message=group-status-member-removed";
       if (!usePageRedirect($_SERVER['HTTP_REFERER'] . $args))
         redirectHeader("home$args");
    } else if ($status == "become_owner" && isMember($group_id, $user_id)) {
      setUsersGroupStatus($group_id, $user_id, "owner");
      $args = "?&group_id=$group_id&message=group-status-member-promoted";
      if (!usePageRedirect($_SERVER['HTTP_REFERER'] . $args))
        redirectHeader("home$args");
    } else {
      redirectHeader("home"); // They tried to hack a pointer;
    }
  } else {
    redirectHeader("home"); // They tried to hack a pointer;
  } 
} else {
  redirectHeader("home"); // They tried to hack a pointer;
}