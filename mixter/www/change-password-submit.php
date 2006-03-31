<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: change-password-submit
// Purpose: This page allows users to change their passwords.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");

checkLoginStatus("in");

$newpassword1 = stripAllTags($_POST["newpassword1"]);
$newpassword2 = stripAllTags($_POST["newpassword2"]);

$valid = validPassword(currentUser(), $_POST['oldpassword']);
if ($valid == "ok") {
  if (isset($newpassword1) && isset($newpassword2) && $newpassword1 !== '') {
    if ($newpassword1 == $newpassword2) {
      setPassword(currentUser(), $newpassword1);
      redirectHeader("home?message=change-password-submit-succesful");
    } else {
      redirectHeader("change-password?error=create-account-nonmatching-password");
    }
  } else {
    redirectHeader("change-password?error=create-account-invalid-password");
  }
} else {
  redirectHeader("change-password?message=change-password-bad-oldpassword");
}