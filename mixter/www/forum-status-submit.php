<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forum-status-submit.php
// Purpose: Lets administrators change the status of forum postings.
// ----------------------------

require_once("../modules/mod-utilities/redirect.lib");
require_once("../modules/mod-content/content-interface.php");

checkLoginStatus("in");

if (isAdmin() && isset($_GET[posting_id]) && ($_GET["posting_action"] == "approved" ||
					       $_GET["posting_action"] == "rejected")) {
  $posting_id = $_GET["posting_id"];
  $posting_action = $_GET["posting_action"];
  if (isPosting($posting_id)) {
    EditorialAction(getPosting($posting_id), $posting_action);
    if (!usePageRedirect($_SERVER['HTTP_REFERER'] . "&message=forum-status-submit-succesful-" . $posting_action))
      redirectHeader("home?&message=forum-status-submit-succesful-" . $posting_action);
  } else {
    redirectHeader("home?error=unknown");
  }
} else {
  redirectHeader("home?error=unknown");
}
