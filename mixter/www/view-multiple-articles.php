<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: view-multiple-articles
// Purpose:Allow a user to view a list of recent articles
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
initializeScript("either");

$num = 10;

if(isset($_GET['num'])) {
  $num = $_GET['num'];
}

$articles = get_last_n_articles($num);
$article_list = generate_multiple_article_view($articles);

$template_variables = compact("article_list","num");

generatePage("view-multiple-articles", "View Articles", evalTemplate("view-multiple-articles.template", $template_variables));
