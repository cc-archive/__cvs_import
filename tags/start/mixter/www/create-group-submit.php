<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: create-group-submit
// Purpose: To submit the data to create a group and display error messages as appropriate.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/page-layout.lib");

initializeScript("in");

$group_name = stripAllTags($_POST["group_name"]);
$group_description = stripAllTags($_POST["group_description"]);
$errorextension = "groupname=$group_name&groupdescription=$group_description";

if (isset($group_name) && $group_name != '') {
  if (isset($group_description) && $group_description != '') {
    if (groupExistsWithName($group_name)) {
      redirectHeader("create-group?error=create-group-name-in-use&$errorextension");
    } else {
      $groupinfo = compact("group_name", "group_description");
      $group_id = insertGroup($groupinfo, getUserID(currentUser()));
      redirectHeader("view-group?message=create-group-succesful-creation&group_id=$group_id");
    }
  } else {
    redirectHeader("create-group?error=create-group-no-description&$errorextension");
  }
} else {
  redirectHeader("create-group?error=create-group-no-name&$errorextension");
}
