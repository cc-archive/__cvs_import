<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: forum-view-postings
// Purpose: view sets of postings from forums. (Mostly for administrators for now)
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("in");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/user-input.lib");

if (!isAdmin()) {
  redirectHeader("home");
}

$editorial_query_sub = ''; // Set this up ahead of time to handle the editorial status stuff.
  // This probably should be handled in the sub functions, but it's just as fast and less coding
  // to put it here. This is what I like to call "denormalizing my scripts."
if (isset($_GET['submitted']))
  $condition[] = "editorial_status='submitted'";
if (isset($_GET['approved']))
  $condition[] = "editorial_status='approved'";
if (isset($_GET['rejected']))
  $condition[] = "editorial_status='rejected'";
if (isset($_GET['expired']))
  $condition[] = "editorial_status='expired'";
if ($condition[0]) {
  $editorial_query_sub = implode(" OR ", $condition);
  $editorial_query_sub = "AND (" . $editorial_query_sub . ") ";
} else {
  $editorial_query_sub = '';
}
  

// Pre: Takes a set of postings objects
// Post: Processes them for display
function processPostings($postings) {
  $view = '';
  if ($postings) {
    while ($posting = get_row_from_multi_row($postings)) {		
      $view .= generatePostingView($posting);
    }   
  } else {
    $view = '<B>No Postings Found</B>';
  }
  return $view;
}

$view_postings = '';

$r_username = '';
$r_new_users = '';
$r_by_forum = '';
$r_all = '';
if ($_GET["select_postings"] == "username")
  $r_username = 'CHECKED';
if ($_GET["select_postings"] == "new_users")
  $r_new_users = 'CHECKED';
if ($_GET["select_postings"] == "by_forum")
  $r_by_forum = 'CHECKED';
if ($_GET["select_postings"] == "all")
  $r_all = 'CHECKED';

$username = stripAllTags($_GET['username']);
$new_users = stripAllTags($_GET['new_users']);

$submitcheck = 'CHECKED';
$approvecheck = 'CHECKED';
$rejectcheck = 'CHECKED';
$expirecheck = '';
if (!isset($_GET["submitted"]))
  $submitcheck = '';
if (!isset($_GET["approved"]))
  $approvecheck = ''; 
if (!isset($_GET["rejected"]))
  $rejectcheck = '';
if (isset($_GET["expired"]))
  $expirecheck = 'CHECKED';

if (isset($_GET["select_postings"])) {
  $select_postings = $_GET["select_postings"];
  if ($select_postings == "all") {
    $select_postings = "new_users";
    $_GET["new_users"] = 365*1000;
  }
  if ($select_postings == "username" && isset($_GET["username"])) {
    $username = stripAllTags($_GET["username"]);
    if (userExists($username)) {
      $userid = getUserID($username);
      $postings = getPostingsOfUser($userid, $editorial_query_sub);
      $view_postings = processPostings($postings);
    } else {
      $_GET['error'] = 'create-account-invalid-username';
    }   
  } else if ($select_postings == "new_users" && isset($_GET["new_users"])) {
    $new_users = stripAllTags($_GET["new_users"]);
    if ($new_users < 0 || $new_users == '') $new_users = 0;
    $postings = getPostingsOfNewUsers($new_users, $editorial_query_sub);
    $view_postings = processPostings($postings);
  } else if ($select_postings == "by_forum") {
    $forum_id = stripAllTags($_GET["forums"]);
    if (isForum($forum_id)) {
      $postings = getPostingsByForum(getForum($forum_id), $editorial_query_sub);
      if ($postings) { // this needs a custom handler because the getPostings returns an array not a sql object.
        foreach ($postings as $posting) {		
          $view_postings .= generatePostingView($posting);
        }   
      } else {
        $view_postings = '<B>No Postings Found</B>';
      }
    }
  }
}

$forums = getListOfNonGroupForums();
$forumlist = '';
if ($forums) { foreach ($forums as $forum) {
  if ($forum->content_id == $_GET['forums']) {
    $checked = 'SELECTED';
  } else {
    $checked = '';
  }
  $forumlist .= "<OPTION VALUE=\"" . $forum->content_id . "\" $checked>" . $forum->one_line_summary . "</OPTION>";
}}

$template_variables = compact("view_postings", "forumlist", "r_username", "r_new_users", "r_by_forum", "r_all", "username",
			      "new_users", "submitcheck", "approvecheck", "rejectcheck", "expirecheck");

generatePage("forum-view-postings", 
	     "View Sets of Forum Postings", 
	     evalTemplate("forum-view-postings.template", $template_variables));