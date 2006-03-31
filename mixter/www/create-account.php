<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page:createaccount
// Purpose:Allow a user to create a new account.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
initializeScript("out");

$username = $_GET['username'];
$given_name = $_GET['given_name'];
$family_name = $_GET['family_name'];
$email = $_GET['email'];
$phone = $_GET['phone'];
$address1 = $_GET['address1'];
$address2 = $_GET['address2'];
$city = $_GET['city'];
$state = $_GET['state'];
$zip = $_GET['zip'];
$country = $_GET['country'];
$male_checked = $_GET['male_checked'];
$female_checked = $_GET['female_checked'];

$template_variables = compact("username", "given_name", "family_name", "email", "phone", "address1", "address2",
                              "city", "state", "zip", "country", "male_checked", "female_checked");

generatePage("create-account", "Create New User Account", evalTemplate("create-account.template", $template_variables));
