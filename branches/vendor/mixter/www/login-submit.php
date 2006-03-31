<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: login-submit.php
// Purpose: This page will actually handle the user login and associated error messages.
// ----------------------------

session_start();

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/page-layout.lib");

if (isset($_POST['username']) && isset ($_POST['password'])) {
  $username = stripAllTags($_POST['username']);
  $password = stripAllTags($_POST['password']);
  if (userExists($username)) {
    $return_status = userLogin($username, $password);
    if ($return_status == "ok") {
      $sourcepage = $_SERVER['HTTP_REFERER'];
      $temp = strstr($sourcepage, $GLOBALS['server_root'] . "welcome");
      if ($temp || !usePageRedirect($sourcepage)) {
        redirectHeader("home");	
      }
    } else if ($return_status == "banned") {
      redirectHeader("welcome?message=login-banned");
    } else {
      redirectHeader("create-account?message=login-bad-password");
    }
  } else {
     redirectHeader("create-account?message=login-no-user");
  }
} else {
  redirectHeader("welcome");
}

?>
