<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: resetpassword
// Purpose: This page actually resets the password and mails the user a new one.
// ----------------------------

require_once("../modules/mod-utilities/globals.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-users/users-interface.php");

if (isset($_POST['username'])) {
  $username = stripAllTags($_POST['username']);
  if (userExists($username)) {
    $userinfo = resetPassword($username);
    $email_address = $userinfo["email"];
    $new_password = $userinfo["password"];

    // Send the User Email
    $adminemail = $GLOBALS['page_admin_email'];
    mail($email_address, 
	 "Password Reset",
          // Probably make this message longer
         "Your Password Has Been Reset! New Password: \"" . $new_password . "\"",
         "From: " . $adminemail . "\r\n" . "Reply-To: " . $adminemail . "\r\n" . "X-Mailer: PHP/" . phpversion());

    redirectHeader("welcome?message=password-mailed&username=" . $username);
  } else {
    redirectHeader("forgot-password?error=reset-password-badusername");
  }
} else {
  redirectHeader("forgot-password");
}

?>

