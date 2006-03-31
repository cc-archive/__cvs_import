<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: search-view
// Purpose: Displays search results to the user.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("either");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-search/search-interface.php");

if (isset($_GET["query"])) {
  $query = stripAllTags($_GET["query"]);
  $search_results = searchFor($query);
  $search_box = longSearchBox($query);
  $template_variables = compact("query", "search_results", "search_box");
  generatePage("search-view", "View Search Results for " . $query, evalTemplate("search-view.template", $template_variables)); 
} else {
  redirectHeader("home");
}