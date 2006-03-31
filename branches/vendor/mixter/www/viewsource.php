<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: viewsource
// Purpose: To let l33t haxors see our source code. 
// ----------------------------

require_once("../modules/mod-utilities/globals.lib");
require_once("../modules/mod-utilities/page-layout.lib");

$document_root = $GLOBALS['document_root'];
$server_root = $GLOBALS['server_root'];

if (isset($_GET['viewsource'])) {
  $short_file = explode('?', $_GET['viewsource']);
  $filename = $short_file[0];
  ob_start();
  highlight_file(str_replace($server_root, $document_root, $filename . ".php"));
  $source = ob_get_contents();
  ob_end_clean();
  $template_variables = compact("source","filename");
  generatePage("viewsource", "View Source", evalTemplate("viewsource.template", $template_variables));
} else { 
  redirectHeader("viewsource?viewsource=viewsource");
}
