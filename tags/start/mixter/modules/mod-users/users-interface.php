<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: users-interface.php
// Purpose: The interface to the users module
// ----------------------------

// Pre: $username is a variable representing a username on the system. $username does not have to valid, or set
// Post: Return a string which is a login form. 
function createLoginForm($username) {
  require_once("../modules/mod-users/login.lib");
  return user_LoginForm($username);
}

// Pre: User is logged in.
// Post: Returns a string which displays who the user is logged in as and what their logout options are.
function createLoggedinForm() {
  require_once("../modules/mod-users/login.lib");
  return user_LoggedinForm();
}

// Pre: Requires that a session has already been started
// Post: Returns whether the user is logged in as administrator or not
function isAdmin() {
  require_once("../modules/mod-users/permissions.lib");
  return users_isAdmin();
}

// Pre: Requires that a session has already been started
// Post: Returns whether the user is logged in or not
function isLoggedIn() {
  require_once("../modules/mod-users/permissions.lib");
  return users_isLoggedIn();
}

// Pre: Takes a $username as a string, representing some possible user on the system. $username has been
// properly escaped.
// Post: Returns true if the user is in the system, and false otherwise.
function userExists($username) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_userExists($username);
}

// Pre: Assumes $username is a valid username in the system
// Post: Returns an array containing the following information
// "email" => The email address of the user
// "password" => The new password of the user
function resetPassword($username) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_resetPassword($username);
}

// Pre: Takes a $email as a string, representing an email address in the system. $email has been 
// properly escaped.
// Post: Returns the email if it was found in the system, false otherwise.
function emailLookup($email) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_emailLookup($email);
}

// Pre: Takes user data ready to insert into the database in $userinfo.
// $userinfo is an array of key value pairs storing user_name, password, given_name,
// family_name, email, phone_number, address_line_1, address_line_2, address_city,
// address_state, address_postal_code, address_country_code, date_joined, time_joined, and gender
// password should not be encrypted when passed in.
// Post: Inserts the user into the database.
function insertUser($userinfo) {
  require_once("../modules/mod-users/userinfo.lib");
  user_insertUser($userinfo);
}

// Pre: Takes $user_id, the user_id for a user on the system
// Post: Returns the object representing that user in the system.
function getUser($user_id) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_getUser($user_id);
}

// Pre: Takes $user_id which is a valid user_id, and a whole bunch of preferences about a users interests.
// Post: Updates that users preferences in the system.
function updateUserInterests($user_id, $interests, $favorite_music_styles, $favorite_music_groups, 
			     $favorite_music_songs, $about_me) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_updateUserInterests($user_id, $interests, $favorite_music_styles, $favorite_music_groups, 
	   	           $favorite_music_songs, $about_me);
}

// Pre: Takes $username which is a valid username, and $unencrypted_password. $username
// must exist in the system.
// which is the password the user tried to use to get access to the system.
// Post: Sets up the session variables that give access and returns a string, one of
// "banned", "badpassword", or "ok"
function userLogin($username, $unencrypted_password) {
  require_once("../modules/mod-users/permissions.lib");
  return validateUserLogin($username, $unencrypted_password);
}

// Pre: Nothing
// Post: Returns the current user
function currentUser() {
  require_once("../modules/mod-users/userinfo.lib");
  return user_currentUser();
}

// Pre: No headers have been sent
// Post: Logs a user out.
function userLogout() {
  require_once("../modules/mod-users/permissions.lib");
  validateUserLogout();
}

// Pre: $user_id is a user_id for a valid user on the system
// Post: Returns that users username.
function getUsername($user_id) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_getUsername($user_id);
}

// Pre: Takes a $user_id, corresponding to a valid user on the system
// Post: Returns a string representing that users publically viewable profile.
function viewProfile($user_id) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_viewProfile($user_id);
}

// Pre: $username is a valid user on the system
// Post: Returns that users user_id
function getUserID($username) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_getUserID($username);
}

// Pre: Takes $username which is a valid username, and $unencrypted_password. $username
// must exist in the system.
// which is the password the user tried to use to get access to the system.
// Post: Returns "badpassword" or "ok" or "banned" as appropriate.
function validPassword($username, $unencrypted_password) { 
  require_once("../modules/mod-users/permissions.lib");
  return users_validPassword($username, $unencrypted_password);
}

// Pre: $username is a user, and $unencrypted_password is the new password for a user
// Post: Sets that as the users new password
function setPassword($username, $unencrypted_password) {
  require_once("../modules/mod-users/userinfo.lib");
  user_setPassword($username, $unencrypted_password);
}

// Pre: Takes a $userid
// Post: Returns true if the user is banned, false if he isn't.
function userBanned($userid) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_userBanned($userid);
}

// Pre: Takes a $userid, and $banned is a true or false, if the user should be banned or unbanned
// Post: Sets the users banned status according to $banned.
function setBannedStatus($userid, $banned) {
  require_once("../modules/mod-users/userinfo.lib");
  return user_setBannedStatus($userid, $banned);
}

// Pre: $query is a valid querystring
// Post: Searches for possible matches to the querystring and returns a set of search results. Search is for users
// and groups related to query.
function searchUsers($query) {
  require_once("../modules/mod-users/users-search.lib");
  return user_searchUsers($query);
}

// Pre: Takes group information ready to insert into the database in $groupinfo.
// $groupinfo is an array of key value pairs storing group_name and group_description.
// $creation_user_id is the user who created the group, and is its first member.
// Post: Creates a group and inserts that user as its owner, returns the group_id.
function insertGroup($groupinfo, $creation_user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_insertGroup($groupinfo, $creation_user_id);
}

// Pre: Takes a $group_id that corresponds to a valid group.
// Post: Deletes the group with that group_id
function deleteGroup($group_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_deleteGroup($group_id);
}

// Pre: Takes a $group_id
// Post: Returns information about the group.
function getGroup($group_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_getGroup($group_id);
}

// Pre: Takes a $group_id
// Post: Returns true if that group exists.
function isGroup($group_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_isGroup($group_id);
}

// Pre: Takes a $group_name which is a group name
// Post: Returns whether a group with that name exists.
function groupExistsWithName($group_name) {
  require_once("../modules/mod-users/groups.lib");
  return groups_groupExistsWithName($group_name);
}

// Pre: Takes a $group_id, and $isAdmin whether the user is an administrator.
// Post: Returns a form showing the status of all group members in the group.
function showUserStatus($group_id, $isAdmin) {
  require_once("../modules/mod-users/groups.lib");
  return groups_showUserStatus($group_id, $isAdmin);
}

// Pre: $group_id is a valid group
// $user_id is a valid user
// Post: Adds that user to the group, as one who can join pending owner approval. 
function addPendingMember($group_id, $user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_addPendingMember($group_id, $user_id);
}


// Pre: $group_id is a valid group
// $user_id is a valid user
// Post: Removes a user from that group
function removeMember($group_id, $user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_removeMember($group_id, $user_id);
}

// Pre: $group_id is a valid group
// $user_id is a valid user in that group
// $status is 'user', 'owner', or 'pending'
function setUsersGroupStatus($group_id, $user_id, $status) {
  require_once("../modules/mod-users/groups.lib");
  return groups_setUsersGroupStatus($group_id, $user_id, $status);
}

// Pre: Takes a $group_id and $user_id
// Post: Returns true if that user is a member of that group.
function isMember($group_id, $user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_isMember($group_id, $user_id);
}

// Pre: Takes a $group_id and $user_id
// Post: Returns true if that user is an owner of that group.
function isOwner($group_id, $user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_isOwner($group_id, $user_id);
}

// Pre: Takes a $group_id
// Post: Returns a form showing all users pending admission/rejection from a group.
// This form should only be visible to administrators.
function showPendingUsers($group_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_showPendingUsers($group_id);
}

// Pre: Takes a $user_id for a valid user
// Post: Returns a display of the groups that user is a member of.
function groupListForMember($user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_groupListForMember($user_id, false);
}

// Pre: Takes a $user_id for a valid user
// Post: Returns a display of the groups that user is a member of, formatted for the left nav bar.
function groupListForMemberLeftBar($user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_groupListForMember($user_id, true);
}

// Pre: Takes a $group_id and $user_id
// Post: Returns true if that user is pending membership of that group.
function isPendingMember($group_id, $user_id) {
  require_once("../modules/mod-users/groups.lib");
  return groups_isPendingMember($group_id, $user_id);
}