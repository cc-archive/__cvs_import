<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: view-article
// Purpose: Allows a user to view article content.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-users/users-interface.php");

initializeScript("either");

$article_id = $_GET['article_id'];
$article_obj = get_article($article_id);
if(isAdmin()) {
  $article = generate_article_admin_view($article_obj);
} else {
  $article = generate_article_view($article_obj);
}
$template_variables = compact("article");

generatePage("view-article", "View Article", evalTemplate("view-article.template", $template_variables));
