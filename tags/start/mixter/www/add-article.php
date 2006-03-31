<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: add-article
// Purpose: Lets a user add an article

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("in");

$user_name = currentUser();

$template_variables = compact("user_name");

generatePage("add-article", "Add an Article", evalTemplate("add-article.template", $template_variables));
