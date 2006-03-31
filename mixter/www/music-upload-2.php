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

$agree = $_GET['agree'];
if ($agree == NULL) {
  redirectHeader("music-upload");
}

$one_line_summary = unstripTags($_GET['songname']);
$description = unstripTags($_GET['description']);
$copyright_holder = unstripTags($_GET['copyright_holder']);
$copyright_year = unstripTags($_GET['copyright_year']);
$source_url = unstripTags($_GET['source_url']);

$_SESSION['refers_to'] = $_GET['refers_to'];
$_SESSION['license_url'] = 'http://creativecommons.org/licenses/by-sa/1.0/';
$_SESSION['license_name'] = 'Attribution-ShareAlike';

$template_variables = compact("one_line_summary","description","copyright_holder","copyright_year","source_url");
generatePage("music-upload-2","Upload Music",evalTemplate("music-upload-2.template", $template_variables));
