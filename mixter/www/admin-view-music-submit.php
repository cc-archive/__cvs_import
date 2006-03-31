<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: admin-view-article-submit
// Purpose: Processes admin actions on articles.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-content/music.lib");
require_once("../modules/mod-utilities/redirect.lib");
require_once("../modules/mod-users/users-interface.php");

checkLoginStatus("in");
checkAdminStatus("admin");

$content_id = $_GET['music_id'];
$editorial_status = $_GET['action'];
if (isset($_GET['referrer'])) {
  $referrer=$_GET['referrer'];
}
else {
  $referrer="home";
}
$user_id = getUserID(currentUser());

switch($editorial_status) {
  case 'reject':
    $editorial_status = 'rejected';
    break;
  case 'approve':
    $editorial_status = 'approved';
  default:
    redirectHeader("home");
}


content_change_music_editorial_status($user_id, $content_id, $editorial_status);

redirectHeader($referrer);

