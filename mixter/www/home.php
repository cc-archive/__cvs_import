<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: home
// Purpose: The first page a user reaches after logging in.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$newcontent = getRecentContent(5);

$user_id = getUserID(currentUser());

$template_variables = compact("admin_home_line", "user_id", "forum_name", "article_admin", "search_administrate", "newcontent", 
			      "admin_general_log_line", "admin_error_log_line");
generatePage("home", "Home", evalTemplate("home.template", $template_variables));
