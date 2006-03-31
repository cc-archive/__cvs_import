<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: admin-view-music
// Purpose: Allows admin to moderate a single music.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
initializeScript("in");

if(!isAdmin()) {
  redirectHeader("home?message=notadmin");
}

if(isset($_GET['music_id'])) {
  $music_id = $_GET['music_id'];
  $music_obj = get_any_music($article_id);
}
else {
  if(!$music_obj = get_next_unmoderated_music())
    redirectHeader("home?message=nothing-to-moderate");
}

$content_id = $music_obj->content_id;
$music = generate_music_view($music_obj);
$template_variables = compact("music","content_id");

generatePage("admin-view-music", "Moderate Music", evalTemplate("admin-view-music.template", $template_variables));
