<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: logout-submit
// Purpose: Logs a user out -- removes all the session information that 
//		identifies him or her as logged in.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");

session_start();

userLogout();

redirectHeader("welcome?message=logout");


