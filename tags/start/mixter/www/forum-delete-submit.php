<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forum-delete-forum-submit
// Purpose: This page allows administrators to create forums
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");

checkLoginStatus("in");

if (isAdmin() && isset($_GET['forum_id'])) {
  deleteForum(stripAllTags($_GET['forum_id']));
  if (!usePageRedirect($_SERVER['HTTP_REFERER']))
    redirectHeader("home");
} else {
  redirectHeader("forums");
}