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

$one_line_summary = unstripTags($_GET['one_line_summary']);
$description = unstripTags($_GET['description']);

$_SESSION['refers_to'] = $_GET['refers_to'];
$_SESSION['license_url'] = $_GET['license_url'];
$_SESSION['license_name'] = $_GET['license_name'];

$template_variables = compact("one_line_summary","description");
generatePage("music-upload-2","Upload Music",evalTemplate("music-upload-2.template", $template_variables));
