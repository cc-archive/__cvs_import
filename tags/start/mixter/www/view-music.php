<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: view-music
// Purpose: Allows a user to view music content.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-users/users-interface.php");

initializeScript("either");

function displayRemix($level, $music_id, $direction, $depth_cutoff, $root_music_id) {
  $music = get_music($music_id);
  $title = unstripTags($music->one_line_summary);
  $artist = unstripTags($music->user_name);
  $date = formatDate($music->creation_date);
  $user_id = $music->user_id;
  $song_link = "view-music?music_id=" . $music_id;
  $artist_link = "user-profile?user_id=" . $user_id;
  $pre = '';
  for ($i = 1; $i < $level; $i++) {
    $pre .= '- ';
  }
  $template_variables = compact("title", "artist", "date", "pre", "song_link", "artist_link");
  
  if ($music_id != $root_music_id) {
    $return_string = evalTemplate("music-remix-song.template", $template_variables);
  }

  if ($direction == "up") {
    $remixes = get_remixes_of($music_id);
  } else {
    $remixes = get_music_remixed_by($music_id);
  }

  while (($level < $depth_cutoff) && $music = get_row_from_multi_row($remixes)) {
    $return_string .= displayRemix($level + 1, $music->content_id, $direction, $depth_cutoff, $root_music_id);
  }
  return $return_string;
}

$music_id = $_GET['music_id'];

$root_music_id = $music_id;
$depth = 1;
if ($_GET['depth'] > 1) {
  $depth = $_GET['depth'];
}

if (isset($root_music_id) && $root_music_id != '') {
  $remix_string_up = displayRemix(0, $root_music_id, "up", $depth, $root_music_id);
  $remix_string_down = displayRemix(0, $root_music_id, "down", $depth, $root_music_id);
  $music = get_music($root_music_id);
  $title = unstripTags($music->one_line_summary);
  $artist = unstripTags($music->user_name);
  $music_id = $root_music_id;
  $template_variables = compact("remix_string_up", "remix_string_down", "artist", "title", "music_id", "depth");
  $remix_tree = evalTemplate("music-remix.template", $template_variables);
} else {
  redirectHeader("welcome");
}

if (isAdmin()) {
  $music_obj = get_any_music($music_id);
  $music = generate_music_admin_view($music_obj);
} else {
  $music_obj = get_music($music_id);
  $music = generate_music_view($music_obj);
}

$users_music = generate_multiple_music_view(get_all_music_by_artist($music_obj->creation_user));

$template_variables = compact("music", "remix_tree", "users_music");

generatePage("view-music", "View Music", evalTemplate("view-music.template", $template_variables));
