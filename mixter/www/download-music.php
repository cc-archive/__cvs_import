<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: download-music
// Purpose: allow a user to download a music file
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-content/content-interface.php");
initializeScript("either");

if(!isset($_GET['content_id'])) {
  redirectHeader("home");
}

$id = $_GET['content_id'];
$music = get_music($id);
$contents = get_music_binary_file_from_obj($music);
$filename = get_music_filename_from_obj($music);
$filesize = get_music_filesize_from_obj($music);
header("Content-Type: audio/mpeg");
header("Content-Length: $filesize");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo $contents;


