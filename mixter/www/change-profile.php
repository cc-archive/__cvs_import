<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: change-profile
// Purpose: Displays the current users profile and lets them change it.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$user = getUser(getUserID(currentUser()));
$homepage = unstripTags($user->homepage);
$publicemail = unstripTags($user->publicemail);
$interests = unstripTags($user->interests);
$favorite_music_styles = unstripTags($user->favorite_music_styles);
$favorite_music_groups = unstripTags($user->favorite_music_groups);
$favorite_music_songs = unstripTags($user->favorite_music_songs);
$about_me = unstripTags($user->about_me);

$template_variables = compact("homepage", "publicemail", "interests", "favorite_music_styles", "favorite_music_groups", "favorite_music_songs", "about_me");
generatePage("change-profile", "Change Your Profile", evalTemplate("change-profile.template", $template_variables));
