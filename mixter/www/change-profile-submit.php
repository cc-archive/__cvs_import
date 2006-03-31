<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: change-profile-submit
// Purpose: Changes a users profile
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$user_id = getUserID(currentUser());

// We don't need to check to make sure these variables are passed with values or whatnot
// because if they aren't and the person was trying to hack the page...ok, so we reset their
// values.

$homepage = stripAllTags($_POST['homepage']);
$publicemail = stripAllTags($_POST['publicemail']);
$interests = stripAllTags($_POST['interests']);
$favorite_music_styles = stripAllTags($_POST['favorite_music_styles']);
$favorite_music_groups = stripAllTags($_POST['favorite_music_groups']);
$favorite_music_songs = stripAllTags($_POST['favorite_music_songs']);
$about_me = stripAllTags($_POST['about_me']);

updateUserInterests($user_id, $interests, $favorite_music_styles, $favorite_music_groups, $favorite_music_songs, $about_me, $homepage, $publicemail);

redirectHeader("change-profile?message=change-profile-submit-succesful");
