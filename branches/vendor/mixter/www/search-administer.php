<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: search-administer
// Purpose: Lets the administrator see statistics and perform tasks on the search functionality. 
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("in");
require_once("../modules/mod-search/search-interface.php");

if (isAdmin()) { 
  $order_by = stripAllTags($_GET["order_by"]);
  $search_statistics = displaySearchStatistics($order_by);
  $corpus_statistics = displayCorpusInfo();
  $template_variables = compact("search_statistics", "corpus_statistics");
  generatePage("search-administer", "Search Administration", evalTemplate("search-administer.template", $template_variables)); 
} else {
  redirectHeader("home");
}