<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forums-specific-thread
// Purpose: view a specific forum thread
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("either");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/user-input.lib");

$forum_id=stripAllTags($_GET['view_forum']);
if ($forum_id !== '' && isForum($forum_id) && isset($_GET['f_id'])) {
  $forum = getForum($forum_id);
  $sourcepage = "forums-specific-thread?view_forum=$forum_id";
  $forum_sourcepage = "forums-specific-forum?view_forum=$forum_id";
  generatePage($sourcepage, "Forums: " . $forum->one_line_summary, displayForum($forum_id, $forum_sourcepage));
} else {
  redirectHeader("forums.php");
}