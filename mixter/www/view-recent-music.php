<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: view-recent-music
// Purpose:Allow a user to view a list of recent music
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
initializeScript("either");

$num = 10;

if(isset($_GET['num'])) {
  $num = $_GET['num'];
}

$musics = get_last_n_music($num);
$music_list = generate_multiple_music_view($musics);


if (isLoggedIn()) {
  $upload_text = createLink("music-upload", "Upload Music Here");
} else {
  $upload_text = "Login to Upload Music";
}
$template_variables = compact("upload_text");

$template_variables = compact("music_list","num");

generatePage("view-multiple-music", "View Music", evalTemplate("view-multiple-music.template", $template_variables));
