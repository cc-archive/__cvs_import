<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: search-interface.php
// Purpose: The interface to the search module
// ----------------------------

// Pre: Nothing
// Post: Returns a quick-search-box for searching in.
function quickSearchBox($searchvalue = '') {
  require_once("../modules/mod-utilities/template.lib");
  return evalTemplate("mod-search/search-box.template", array("searchvalue" => $searchvalue, "size" => "15"));
}

// Pre: Nothing
// Post: Returns a long-search-box for searching in.
function longSearchBox($searchvalue = '') {
  require_once("../modules/mod-utilities/template.lib");
  return evalTemplate("mod-search/search-box.template", array("searchvalue" => $searchvalue, "size" => "60"));
}


// Pre: $query is a string representing a query, which has been stripped of malicious tags
// Post: Returns the string representing the search results.
function searchFor($query) {
  require_once("../modules/mod-search/search.lib");
  return search_searchFor($query);
}

// Pre: $order_by is 'phrase', 'number', or 'date'
// Post: Returns search statistics regarding the full text indexes.
function displaySearchStatistics($order_by) {
  require_once("../modules/mod-search/search.lib");
  return search_displaySearchStatistics($order_by);
}

// Pre: Nothing
// Post: Returns information about the corpus of documents incorporated in the search.
function displayCorpusInfo() {
  require_once("../modules/mod-search/search.lib");
  return search_displayCorpusInfo();
}
