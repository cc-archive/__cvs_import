<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: group-delete-submit
// Purpose: To delete a group
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
initializeScript("in");

$group_id=$_GET["group_id"];

if (isAdmin()) {
  if (isset($group_id) && $group_id != '' && isGroup($group_id)) {
    deleteGroup($group_id);
    redirectHeader("home?message=group-delete-submit-succesful");
  } else {
    redirectHeader("home"); // They tried to hack the system
  }
} else {
  redirectHeader("home"); // They tried to hack the system
}