<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: music-upload
// Purpose: Uploads music into the system.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

if (isset($_GET['group_id']) && isGroup($_GET['group_id'])) {
  $refers_to = $_GET['group_id'];
} else {
  $refers_to = '';
}

$template_variables = compact("refers_to");
generatePage("music-upload", "Upload Music", evalTemplate("music-upload.template", $template_variables));
