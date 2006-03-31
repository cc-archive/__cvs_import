<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: music-upload-cc-refer
// Purpose: Sends user to CC for licensing.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$refers_to = stripAllTags($_GET['refers_to']);
$one_line_summary = stripAllTags($_GET['songname']);
$description = stripAllTags($_GET['description']);

$_SESSION['title'] = $one_line_summary;

$cc_url = "http://creativecommons.org/license/?partner=mixter&exit_url=" . $GLOBALS['server_root'] . "music-upload-2?license_url=[license_url]%26license_name=[license_name]";
$cc_url .= "%26refers_to=$refers_to%26one_line_summary=$one_line_summary%26description=$description";

redirectHeader("$cc_url");
