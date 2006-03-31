<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forum-delete-user
// Purpose: This page allows administrators to delete blatantly unruly users.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");

checkLoginStatus("in");

// Variables that must be set:

if (isAdmin() && $_GET['userid']) {
  $userid = stripAllTags($_GET['userid']);
  if (userExists(getUsername($userid))) {
    rejectAllPostingsByUser($userid);
    if (!usePageRedirect($_SERVER['HTTP_REFERER']))
      redirectHeader("home");
  } else {
    redirectHeader("home"); // User tried to hack the link
  }
} else {
  redirectHeader("home"); // User tried to hack the link
}