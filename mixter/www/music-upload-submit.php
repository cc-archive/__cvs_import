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

$refers_to = $_SESSION['refers_to'];
$license_url = $_SESSION['license_url'];
$license_name = $_SESSION['license_name'];
$one_line_summary = stripAllTags($_POST['one_line_summary']);
$source_url = stripAllTags($_POST['source_url']);

$description = stripAllTags($_POST['description']);

$creation_user = getUserID(currentUser());
$viewable_status = "public"; // LATER ???
$language = "en"; // NOT HARDCODE THIS
$filename = currentUser() . " - " . $one_line_summary . ".mp3"; // Support for Not Mp3s later
$mime_type = $_FILES['userfile']['type'];
$temp_filename = $_FILES['userfile']['tmp_name'];


if (isset($one_line_summary) && isset($description) && isset($language) && isset($temp_filename)) {
  $success = add_new_music($refers_to, $creation_user, $viewable_status, 
                           $license_name, $license_url, $one_line_summary, 
                           $description, $language,
	                   $filename, $mime_type, $temp_filename, $copyright_holder, $copyright_year, $source_url);
  if($success == FALSE) {
    // Error messages
  }
  if (isset($_POST['remix'])) {
    $_SESSION['content_id'] = $success;
//    $_SESSION['title'] = $one_line_summary;
    redirectHeader("music-upload-remix");
  } else {
    redirectHeader("music-upload?message=music-upload-succesful");
  }
} else {
  // Error messages as appropriate - fill in later.
}
