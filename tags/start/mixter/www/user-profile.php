<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: user-profile
// Purpose: Displays a users profile. Must be passed with the get parameter user_id, corresponding to a valid
//          user_id.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-content/content-interface.php");

initializeScript("either");

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

if (currentUser() && $_GET['user_id'] == '') {
  $_GET['user_id'] = getUserID(currentUser());
}

if ((isset($_GET["user_id"]) && $_GET["user_id"] !== '') || currentUser()) {
  $user_id = stripAllTags($_GET["user_id"]);
  $users_music = generate_multiple_music_view(get_all_music_by_artist($user_id));
  
  // This is where the music code should go - set a variable, add it to the template variables,
  // and add it to the template
  if ($user_info_fields = viewProfile($user_id)) {
    if (isAdmin()) {
      $username = getUsername($user_id);
      if (userBanned($user_id)) {
        $user_status = "Banned";
	$status_action = createLink("ban-user-submit?ban=false&username=$username", "Unban");
      } else {
	$user_status = "Active";
	$status_action = createLink("ban-user-submit?ban=true&username=$username", "Ban");
      }
      $template_variables = array("username" => $username, "user_status" => $user_status, "userid" => $user_id, 
				  "status_action" => $status_action);
      $adminterface = evalTemplate("user-profile-adminterface.template", $template_variables);
    } else {
      $adminterface = ''; 
    }
 
    $username = getUsername($user_id);
    if (currentUser() && $user_id == getUserID(currentUser())) {
      $user_options = "<TR><TD>" . createLink("change-password", "change your password") . "</TD></TR>" .
		      "<TR><TD>" . createLink("change-profile", "edit your profile") . "</TD></TR>" .   
		      "<TR><TD>" . createLink("music-upload", "upload some music") . "</TD></TR>";
      $leftbar = evalTemplate("user-profile-change-info-pretty.template", compact("user_options"));
    }
    $leftbar .= evalTemplate("user-profile-leftbar.template", compact());
    $postings = getPostingsOfUser($user_id, "AND (editorial_status='approved' OR editorial_status='submitted') ");
    $view_postings = processPostings($postings);
    $group_memberships = groupListForMember($user_id);
    $template_variables = compact("user_info_fields", "username", "user_options", "adminterface", "view_postings", "group_memberships", "users_music");  
  } else {
    $username = "(nobody)";
    $user_info_fields = "No User Found";
    $template_variables = compact("user_info_fields", "username", "adminterface", "group_memberships");
  }
  generatePage("user-profile", "View User Profile: " . $username, evalTemplate("user-profile.template", $template_variables), $leftbar);
} else {
  redirectHeader("home");
}
