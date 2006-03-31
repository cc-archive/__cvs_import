<?php
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page:welcome
// Purpose:The first page the user sees when they try to use mixster.
//         This page will mostly just be a login screen.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-content/content-interface.php");

initializeScript("either");

$newcontent = getRecentContent(5);
$template_variables = array("newcontent" => $newcontent);

generatePage("welcome", "Welcome to Mixter", evalTemplate("welcome.template", $template_variables));

?>
