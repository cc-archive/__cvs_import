<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: createaccountsubmit
// Purpose: To submit the data to create an account and display error messages as appropriate.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-utilities/page-layout.lib");

$given_name = stripAllTags($_POST['given_name']);
$family_name = stripAllTags($_POST['family_name']);
$phone = stripAllTags($_POST['phone_number']);
$address1 = stripAllTags($_POST['address1']);
$address2 = stripAllTags($_POST['address2']);
$city = stripAllTags($_POST['city']);
$state = stripAllTags($_POST['state']);
$zip = stripAllTags($_POST['zip']);
$country = stripAllTags($_POST['country']);
$country = "US"; // FIX LATER - FOR NOW ASSUME USA - Matt
$gender = stripAllTags($_POST['gender']);
$username = stripAllTags($_POST['username']);
$password1 = stripAllTags($_POST['password1']);
$password2 = stripAllTags($_POST['password2']);
$email = stripAllTags($_POST['email']);
if ($gender == "Female") {
  $male_checked = '';
  $female_checked = 'CHECKED';
} else {
  $male_checked = 'CHECKED';
  $female_checked = '';
}

$errorextension = "username=$username&given_name=$given_name&" .
                  "family_name=$family_name&email=$email&phone_number=$phone_number&address1=$address1&" . 
                  "address2=$address2&city=$city&state=$state&zip=$zip&country=$country&male_checked=$male_checked" . 
		  "&female_checked=$female_checked";

if (isset($_POST['username']) && $_POST['username'] != "") {
  if (isset($_POST['password1']) && $_POST['password1'] != "") {
    if ($password1 == $password2) {
      if (isset($_POST['email']) && $_POST['email'] != "") {
        if (userExists($username)) {
	  redirectHeader("create-account?error=create-account-username-taken&$errorextension");
        } else {
	  if (emailLookup($email)) {
	    redirectHeader("create-account?error=create-account-email-taken&$errorextension");
          } else {
            if ($gender == "Female") {
              $gender = "F";
            } else {
              $gender = "M";
            }
            $date_joined = date("Y-m-d H:i:s");
	    $time_joined = date("G:i");
	    $password_unencrypted = $password1;

	    $userinfo = compact("username", "password_unencrypted", "given_name", "family_name", "email", 
 				"phone_number", "address1", "address2", "city", "state", "zip", "country", 
				"date_joined", "time_joined", "gender");
	    insertUser($userinfo);
 	    userLogin($username, $password_unencrypted);
  	    redirectHeader("home");
          } 
        }
      } else {
        redirectHeader("create-account?error=create-account-invalid-email&$errorextension");
      }
    } else {
      redirectHeader("create-account?error=create-account-nonmatching-password&$errorextension");
    }
  } else {
    redirectHeader("create-account?error=create-account-invalid-password&$errorextension");
  }
} else {
  redirectHeader("create-account?error=create-account-invalid-username&$errorextension");
}
