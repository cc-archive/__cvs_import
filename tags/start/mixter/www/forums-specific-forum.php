<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forums-specific-forum
// Purpose: view a specific forum
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("either");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/user-input.lib");

$forum_id=stripAllTags($_GET['view_forum']);
if ($forum_id !== '' && isForum($forum_id)) {
  $forum = getForum($forum_id);
  $sourcepage = "forums-specific-forum?view_forum=$forum_id";
  $specific_thread_sourcepage = "forums-specific-thread?view_forum=$forum_id";
  $threads = generateAllThreads($forum, $specific_thread_sourcepage);
  $forumtitle = unstripTags($forum->one_line_summary);
  $template_variables = compact("threads", "forumtitle");
  generatePage($sourcepage, "Forums: " . $forumtitle, evalTemplate("forums-specific-forum.template", $template_variables));
} else {
  redirectHeader("forums.php");
}