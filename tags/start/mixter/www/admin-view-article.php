<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: admin-view-article
// Purpose: Allows admin to moderate a single article.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
initializeScript("in");

if(isset($_GET['article_id'])) {
  $article_id = $_GET['article_id'];
  $article_obj = get_any_article($article_id);
}
else {
  if(!$article_obj = get_next_unmoderated_article())
    redirectHeader("home?message=nothing-to-moderate");
}

$content_id = $article_obj->content_id;
$article = generate_article_view($article_obj);
$template_variables = compact("article","content_id");

generatePage("admin-view-article", "Moderate Article", evalTemplate("admin-view-article.template", $template_variables));
