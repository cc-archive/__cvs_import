<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forum-post-submit
// Purpose: This page allows users to submit posts to forums.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");

checkLoginStatus("in");

// Variables that must be set:
if (isset($_POST['subject']) && isset($_POST['body']) && isset($_POST['refers_to'])
    && $_POST['body'] != '' && $_POST['subject'] != '') {
  $subject = stripAllTags($_POST['subject']);
  $body = stripSomeTags($_POST['body']);
  $refers_to = stripAllTags($_POST['refers_to']);
  if (isForum($refers_to) || isPosting($refers_to)) {
    if (isForum($refers_to) && $_POST['confirmed'] == '') { // Special Handler - Do we redirect - Matt?
      // ADD MORE HERE!!!
      $body = urlencode($_POST['body']);
      $refers_to = urlencode($_POST['refers_to']);
      $subject = urlencode($_POST['subject']);
      redirectHeader("confirm-posting?subject=$subject&body=$body&refers_to=$refers_to&back_to_page=" . urlencode($_SERVER['HTTP_REFERER']));
      exit();
    }
    $username = getUserID(stripAllTags(currentUser()));
    insertPosting($username, $refers_to, $subject, $body);
    if (!usePageRedirect($_POST['confirmed']) && !usePageRedirect($_SERVER['HTTP_REFERER']))
      redirectHeader("home");
  } else {
    redirectHeader("home?error=unknown");
  }
} else {
  $returnpage = $_SERVER['HTTP_REFERER'] . "?&error=forum-post-submit-incorrect-posting";
  if (!usePageRedirect($returnpage))
    redirectHeader("home?error=forum-post-submit-incorrect-posting");
}
