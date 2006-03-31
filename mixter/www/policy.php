<?
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-content/content-interface.php");

initializeScript("in");

$user_id = getUserID(currentUser());

$template_variables = compact("user_id");
generatePage("policy", "Policy", evalTemplate("policy.template", $template_variables));
