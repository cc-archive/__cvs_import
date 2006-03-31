<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: usernamelookupsubmit
// Purpose: This page lets them know the results of their by-email address search.
// ----------------------------

require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");

if (isset($_POST['email'])) {
  $email = stripAllTags($_POST['email']);
  $username = emailLookup($email);
  if ($username) {
    redirectHeader("username-lookup-found?username=$username");   
  } else {
    redirectHeader("username-lookup?error=username-lookup-bademail");
  }
} else {
  redirectHeader("username-lookup");
}