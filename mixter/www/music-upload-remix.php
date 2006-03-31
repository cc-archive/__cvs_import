<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: music-upload-remix
// Purpose: Uploads music into the system.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

// Needs stuff to query-display search information!
if(isset($_GET['query'])) {
  $results = search_music(stripAllTags($_GET['query']));
  $search_results = generate_multiple_music_view_checkboxes($results);
}

$title = $_SESSION['title'];
$music_id = $_SESSION['content_id'];
#var_dump($_SESSION);

$template_variables = compact("title", "search_results", "music_id");
generatePage("music-upload-remix", "Upload Music: Remix", evalTemplate("music-upload-remix.template", $template_variables));
