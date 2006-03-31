<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forum-create-forum-submit
// Purpose: This page allows administrators to create forums
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");

checkLoginStatus("in");

if (isAdmin() && isset($_POST['one_line_summary']) && isset($_POST['description'])) {
  $description = stripAllTags($_POST['description']);
  $one_line_summary = stripAllTags($_POST['one_line_summary']);
  if ($description == '') {
    redirectHeader("forums?error=forum-create-forum-submit-no-name");
  } else if ($one_line_summary == '') {
    redirectHeader("forums?error=forum-create-forum-submit-no-description");
  } else {
    createForum($one_line_summary, $description);
    if (!usePageRedirect($_SERVER['HTTP_REFERER']))
      redirectHeader("home");
  }
} else {
  redirectHeader("forums");
}