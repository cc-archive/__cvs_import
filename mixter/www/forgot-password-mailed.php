<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page:passwordmailed
// Purpose: This page lets them login after their user password has been reset.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("out");

require_once("../modules/mod-users/users-interface.php");

$loginform = createLoginForm($_GET['username']);
$template_variables = array("loginform" => $loginform);

generatePage("welcome", "Password Mailed", evalTemplate("forgot-password-mailed.template", $template_variables));

?>