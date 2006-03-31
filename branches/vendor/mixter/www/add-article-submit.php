<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: add-article-submit
// Purpose: Processes submitted articles.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");
require_once("../modules/mod-users/userinfo.lib");

checkLoginStatus("in");

$one_line_summary = stripSomeTags($_POST['one_line_summary']);
$description = stripSomeTags($_POST['description']);
$user_id = user_getUserID(stripSomeTags($_POST['user']));
$body = stripSomeTags($_POST['body']);
$viewable_status = "public";
$license_name = null;
$license_url = null;
$refers_to = null;
$language = "en";

add_new_article($body, $refers_to, $user_id, $viewable_status, $license_name, 
                  $license_url, $one_line_summary, $description, $language);

redirectHeader("home?message=add-article-submit-successful");
