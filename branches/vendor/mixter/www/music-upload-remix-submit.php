<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: music-upload-submit
// Purpose: Uploads music into the system.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$content_id = $_SESSION['content_id'];

if(isset($_POST['done'])) {
  redirectHeader("view-music?music_id=$content_id");
}

if(!isset($_POST['song'])) redirectHeader("music-upload-remix");

$remix = $_POST['song'];

foreach($remix as $remix_id => $remix_tf) {
  if($remix_tf) {
    add_remix($content_id,$remix_id);
  }
}

redirectHeader("music-upload-remix?message=music-upload-remix-succesful");
