<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: ban-user
// Purpose: This page allows administrators to ban or unban users.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/redirect.lib");

checkLoginStatus("in");

if (isAdmin() && isset($_GET["ban"]) && isset($_GET["username"])) {
  $username = stripAllTags($_GET["username"]);
  if (userExists($username)) {
    if ($_GET["ban"] == "true" || $_GET["ban"] == "false") {
      setBannedStatus(getUserID($username), $_GET["ban"]);
      if (!usePageRedirect($_SERVER['HTTP_REFERER'] . "&message=ban-user-submit-succesful-" . $_GET["ban"]))
        redirectHeader("home" . "&message=ban-user-submit-succesful-" . $_GET["ban"]);
    } else {
      redirectHeader("home"); // they tried to hack the link, so there's no need for an explicit error message
    }
  } else {
    redirectHeader("home"); // they tried to hack the link, so there's no need for an explicit error message
  }
} else {
  redirectHeader("home"); // they tried to hack the link, so there's no need for an explicit error message
}