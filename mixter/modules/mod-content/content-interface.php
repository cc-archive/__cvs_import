<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: content-interface.php
// Purpose: The interface to the content module
// ----------------------------

// Pre: Takes a $posting object, $editorial_status must be "approved" or "rejected"
// Post: Changes the editorial status of that object.
function EditorialAction($posting, $editorial_status) {
  require_once("../modules/mod-content/new-forums.lib");
  content_EditorialAction($posting, $editorial_status);
}

// Pre: Checks to see if $content_id corresponds to a valid forum.
// Post: Returns true or false.
function isForum($content_id) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_isForum($content_id);
}

// Pre: Checks to see if $content_id corresponds to a valid posting.
// Post: Returns true or false.
function isPosting($content_id) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_isPosting($content_id);
}

// Pre: Takes $posting_id which corresponds to a question.
// Post: Returns a posting object representing the posting.
function getPosting($posting_id) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getPosting($posting_id);
}

// Pre: Takes $forum_id which corresponds to a forum. 
// Assumes that $forum_id is a real forum in the database.
// Post: Returns a forum object representing the forum
function getForum($forum_id) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getForum($forum_id);
}

// Pre: Takes $group_id which corresponds to a group.
// Post: Returns the $forum_id of that group's forum in the database.
function getGroupForum($group_id) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getGroupForum($group_id);
}

// Pre: Takes $forum which represents a forum object.
// Post: Returns a string showing the AllThreads view for that forum.
// $sourcepage is the sourcepage that the forum gets displayed on. It should already have the "?" at the end of it.
// It should be relative to the server root.
function generateAllThreads($forum, $sourcepage) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_generateallThreads($forum, $sourcepage);
}

// Pre: Takes $posting which represents a posting object.
// Post: Returns a string representing that object as a question.
function generateQuestionView($posting) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_generateQuestionView($posting);
}

// Pre: Takes $posting which represents a posting object.
// Post: Returns a string representing that object as an answer. 
function generateAnswerView($posting) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_generateAnswerView($posting);
}

// Pre: Takes $posting which represents a posting object.
// Post: Returns a string representing that object as a generic posting.
function generatePostingView($posting) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_generatePostingView($posting);
}

// Pre: Takes $postings which represents a list of answers.
// Post: Returns a string representing those postings as a set of answers.
function generateAllAnswersView($postings) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_generateAllAnswersView($postings);
}

// Pre: Takes $question_and_answers which is an array of "question" and "answers" representing the question and answers 
// from the database
// Post: Returns a string representing the entire set of questions and answers. 
// $sourcepage is the sourcepage that the forum gets displayed on. It should already have the "?" at the end of it.
// It should be relative to the server root.
function generateThread($question_and_answers, $sourcepage) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_generateThread($question_and_answers, $sourcepage);
}

// Pre: Given $question_id, which is a valid id of a question in a forum
// Post: Returns an array containing "question" which is the query with the corresponding content_id,
// and "answers" which is a subarray containing a set of all pieces of content that refer to that question.
// $sourcepage is the sourcepage that the forum gets displayed on. It should already have the "?" at the end of it.
// It should be relative to the server root.
function getQuestionAndAnswers($question_id, $sourcepage) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getQuestionAndAnswers($question_id, $sourcepage);
}

// Pre: $creation_user is the author of the posting, $refers_to is the content this refers to
// (an answer to a question, or a forum), $one_line_summary is the short subject line, and 
// $body is the full body of the message.
// Post: Adds a message to a message board.
function insertPosting($creation_user, $refers_to, $one_line_summary, $body) {
  require_once("../modules/mod-content/new-forums.lib");
  content_insertPosting($creation_user, $refers_to, $one_line_summary, $body);
}

// Pre: Assumes $forum_id is the id for a legitimate forum. $_GET['f_id'] can also be set.
// If that is set, this will display that instead. That is how pages can get reloaded showing
// Different things.
// $sourcepage is the sourcepage that the forum gets displayed on. It should already have the "?" at the end of it.
// It should be relative to the server root.
// Post: Returns a string representing the forum information to be displayed. 
function displayForum($forum_id, $sourcepage) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_displayForum($forum_id, $sourcepage);
}

// Pre: Nothing
// Post: Returns a list of forum objects representing forums.
function getListOfNonGroupForums() {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getListOfNonGroupForums();
}

// Pre: Takes $forums, which is a list of forum objects.
// Post: Returns a string that represents the forum objects.
function viewListOfForums($forums, $sourcepage) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_viewListOfForums($forums, $sourcepage);
}

// Pre: $one_line_summary and $description are optional variables that have default
// values for those paramters if set.
// Post: Returns the form that lets the user create forums.
function adminForumView($one_line_summary, $description) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_adminForumView($one_line_summary, $description);
}

// Pre: $one_line_summary and $description are variables that have the values neccessary
// to create a forum of that name.
// Post: Creates the forum
function createForum($one_line_summary, $description) {
  require_once("../modules/mod-content/new-forums.lib");
  content_createForum($one_line_summary, $description, true);
}

// Pre: $one_line_summary and $description are variables that have the values neccessary
// to create a forum of that name. $group_id is the group_id a forum belongs to.
// Post: Creates a group forum
function createGroupForum($one_line_summary, $description, $group_id) {
  require_once("../modules/mod-content/new-forums.lib");
  content_createForum($one_line_summary, $description, false, $group_id);
}

// Pre: $forum_id is the forum_id of a valid forum
// Post: Soft Deletes the forum
function deleteForum($forum_id) {
  require_once("../modules/mod-content/new-forums.lib");
  content_deleteForum($forum_id);
}

// Pre: $userid is a valid userid;
// $editorialdata is a subquery that can be inserted into a sequel query to limit content to certain editorial types.
// Post: Returns a list of all postings by that user.
function getPostingsOfUser($userid, $editorialdata) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getPostingsOfUser($userid, $editorialdata);
}

// Pre: $days is a number greater than or equal to zero representing the most days that can have
// past before a user account was created.
// $editorialdata is a subquery that can be inserted into a sequel query to limit content to certain editorial types.
// Post: Returns all postings by recent users created within that last amount of time.
function getPostingsOfNewUsers($days, $editorialdata) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getPostingsOfNewUsers($days, $editorialdata);
}

// Pre: Takes a $forum, which is an object representing a forum
// it should have the fields content_id.
// $editorialdata is a subquery that can be inserted into a sequel query to limit content to certain editorial types.
// Post: Returns an array containing posting objects, which represent postings.
function getPostingsByForum($forum, $editorialdata) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_getPostingsByForum($forum, $editorialdata);
}

// Pre: Takes a $userid that is a valid userid on the system.
// Post: Marks all posts by that user as rejected.
function rejectAllPostingsByUser($userid) {
  require_once("../modules/mod-content/new-forums.lib");
  return content_rejectAllPostingsByUser($userid);
}

//
// ARTICLES
//

// Pre: Takes a $articleid that is a valid articleid on the system.
// Post: Returns an article object representing that article, as long
//       as the article is marked approved.
function get_article($articleid) {
  require_once("../modules/mod-content/articles.lib");
  return content_get_article($articleid);
}

// Pre: Takes an $articleid corresponding to a valid article object.
// Post: Returns an article object to be passed to other functions of this
// library.

function get_any_article($articleid) {
  require_once("../modules/mod-content/articles.lib");
  return content_get_any_article($articleid);
}

// Pre: $article is a valid article object
// Post: returns the content_id integer corresponding to $article

function get_id_of_article($article) {
  require_once("../modules/mod-content/articles.lib");
  return content_get_id_of_article($article);
}

// Pre: $num is an integer.
// Post: Returns a multirow object of articles, to be passed to other functions
// of this library.

function get_last_n_articles($num) {
  require_once("../modules/mod-content/articles.lib");
  return content_get_last_n_articles($num);
}

// Pre: none.
// Post: Returns the most recently submitted unmoderated article object.

function get_next_unmoderated_article() {
  require_once("../modules/mod-content/articles.lib");
  return content_get_next_unmoderated_article();
}

// Pre: $article is a valid approved article object.
// Post: Returns HTML to display the article object.

function generate_article_view($article) {
  require_once("../modules/mod-content/articles.lib");
  return content_generate_article_view($article);
}

// Pre: $article is a valid article object.
// Post: Returns HTML to display the article object, with moderation links.

function generate_article_admin_view($article) {
  require_once("../modules/mod-content/articles.lib");
  return content_generate_article_admin_view($article);
}

// Pre: $articles is a multirow list of article objects, generated by this
// library.
// Post: Returns HTML to display all the article in a list format.

function generate_multiple_article_view($articles) {
  require_once("../modules/mod-content/articles.lib");
  return content_generate_multiple_article_view($articles);
}

// Pre: $article is a valid article object.
// Post: Returns HTML to display a summary of the article.

function generate_article_summary($article) {
  require_once("../modules/mod-content/articles.lib");
  return content_generate_article_view($article);
}

// Pre: $body is a string of SQL-safe text.
//      $refers_to is an integer corresponding to the content_id of a piece of
//      content in the database, or NULL.
//      $creation_user is an integer corresponding to the user_id of the user
//      who created this article
//      $viewable_state is a string, either "private" or "public" or NULL.
//      $license is a URL to a valid Creative Commons license, or NULL.
//      $one_line_summary is a string representing the title of the article of
//      length <= 100 chars.
//      $description is a string describing the article, of length <= 300
//      chars.
//      $language is a two-letter ISO language code indicated the language of
//      the article.
// Post: Attempts to add the article to the database.  Returns true if
// successful, and false otherwise.

function add_new_article($body, $refers_to, $creation_user, $viewable_status, 
    $license_name, $license_url, $one_line_summary, $description, $language) {
  require_once("../modules/mod-content/articles.lib");
  return content_add_new_article($body, $refers_to, $creation_user, $viewable_status, 
    $license_name, $license_url, $one_line_summary, $description, $language);
    
    }

// Pre: $editor_id is the id of a user
//      $content_id is the id of a piece of content in the database
//      $new_status is either "approved", "rejected", "expired", or "submitted"
// Post: Attempts to change the editorial status of the object to
// $editorial_version; returns true if successful, false otherwise.

function change_article_editorial_status($editor_id, $article_id, $new_status) {
  require_once("../modules/mod-content/articles.lib");
  return content_change_article_editorial_status($editor_id, $article_id, $new_status);
}

//
// MUSIC SECTION
//

// Pre: $music_id corresponds to a valid 

function get_music($music_id) {
  require_once("../modules/mod-content/music.lib");
  return content_get_music($music_id);
}
 
function get_any_music($music_id) { 
  require_once("../modules/mod-content/music.lib");
  return content_get_any_music($music_id);
}

function get_all_music_by_artist($artist_id) {
  require_once("../modules/mod-content/music.lib");
  return content_get_all_music_by_artist($artist_id);
}

// Pre: $artist_id corresponds to a valid user in the database, and num is an
// integer.
// Post: Returns a multirow object containing music objects that can be passed
// to other functions in this library.  It contains the num most recent
// approved music objects created by this author.

function get_n_music_by_artist($artist_id, $num) {
  require_once("../modules/mod-content/music.lib");
  return content_get_n_music_by_artist($artist_id, $num);
}

// Pre: $music is a valid music object generated by this library.
// Post: returns the user id of the author of this piece of music.

function get_artist_of_music($music) {
   require_once("../modules/mod-content/music.lib"); 
   return content_get_artist_of_music($music);
}

// Pre: $num is an integer.
// Post: Returns the num most recently created music objects that are approved.

function get_last_n_music($num) {
  require_once("../modules/mod-content/music.lib");
  return content_get_last_n_music($num);
}

// Pre: None.
// Post: Returns the music object corresponding ot the most recently submitted
// unmoderated piece of music, or false if there is no unmoderated music.

function get_next_unmoderated_music() {
  require_once("../modules/mod-content/music.lib");
  return content_get_next_unmoderated_music();
}

// Pre: $new_song is the id of the song doing the mixing/sampling, and
// $remixed_song is the id of the song being remixed/sampled.  They both
// correspond to valid music objects in the database.
// Post: Returns true if the relation was successfully added to the database,
// and false otherwise.

function add_remix($new_song, $remixed_song) {
  require_once("../modules/mod-content/music.lib");
  return content_add_remix($new_song, $remixed_song);
}

//Pre: $music_id corresponds to a valid music object in the database.
//Post: Returns a multi-row object containing music objects corresponding to
//the music that remixed the piece specified by $music_id

function get_remixes_of($music_id) {
  require_once("../modules/mod-content/music.lib");
  return content_get_remixes_of($music_id);
}

// Pre: $music_id corresponds to a valid music object in the database
// Post: Returns a multi-row object containing music objects corresponding to
// the music that $music_id remixes.

function get_music_remixed_by($music_id) {
  require_once("../modules/mod-content/music.lib");
  return content_get_music_remixed_by($music_id);
}

// Pre: $music_id corresponds to a valid music object in the database.
// Post: returns a string containing the binary representation of the music
// file.

function get_music_binary_file($music_id) {
  require_once("../modules/mod-content/music.lib");
  return content_get_music_binary_file($music_id);
}

// Pre: $music is a valid music object.
// Post: returns a string containing the binary representation of the music
// file, which can be echoed for download.

function get_music_binary_file_from_obj($music) {
  require_once("../modules/mod-content/music.lib");
  return content_get_music_binary_file_from_obj($music);
}

function get_music_filename_from_obj($music) {
  require_once("../modules/mod-content/music.lib");
  return content_get_music_filename_from_obj($music);
}

function get_music_filesize_from_obj($music) {
  require_once("../modules/mod-content/music.lib");
  return content_get_music_filesize_from_obj($music);
}

// Pre: $music is a valid approved music object.
// Post: Returns HTML to display the music object, depends on
// mod-content/templates/music.template

function generate_music_view($music) {
  require_once("../modules/mod-content/music.lib");
  return content_generate_music_view($music);
}

// Pre: $music is a valid music object.
// Post: Returns HTML to display the music object, with moderation links.

function generate_music_admin_view($music) {
  require_once("../modules/mod-content/music.lib");
  return content_generate_music_admin_view($music);
}

// Pre: $musics is a multirow list of music objects generated by this library.
// Post: Returns HTML to display a list of all the music objects in the list.

function generate_multiple_music_view($musics) {
  require_once("../modules/mod-content/music.lib");
  return content_generate_multiple_music_view($musics);
}

// Pre: $musics is a multirow list of music objects generated by this library.
// Post: Returns HTML to display a list of all the music objects in the list,
// but with checkbox form fields.  Basically, this is used for the remix form.

function generate_multiple_music_view_checkboxes($musics) {
  require_once("../modules/mod-content/music.lib");
  return content_generate_multiple_music_view_checkboxes($musics);
}


// Pre: $music is a valid music object.
// Post: Returns HTML to display a summary of the music object.

function generate_music_summary($music) {
  require_once("../modules/mod-content/music.lib");
  return content_generate_music_summary($music);
}

// Pre: $body is a string of SQL-safe text.
//      $refers_to is an integer corresponding to the content_id of a piece of
//      content in the database, or NULL.
//      $creation_user is an integer corresponding to the user_id of the user
//      who created this article
//      $viewable_state is a string, either "private" or "public" or NULL.
//      $license is a URL to a valid Creative Commons license, or NULL.
//      $one_line_summary is a string representing the title of the music of
//      length <= 100 chars.
//      $description is a string describing the music, of length <= 300
//      chars.
//      $language is a two-letter ISO language code indicated the language of
//      the music.
//      $filename is the name of the file as it will appear when downloaded, eg
//      "example.mp3".
//      $temp_filename is the name of the file's temporary location as obtained
//      from $_FILES['userfile']['tmp_name'].
// Post: Attempts to add the music to the database.  Returns true if
// successful, and false otherwise.

function add_new_music($refers_to, $creation_user, $viewable_status, $license_name, $license_url, $one_line_summary, $description, $language, $filename, $mime_type, $temp_filename, $copyright_holder, $copyright_year, $source_url) {
  require_once("../modules/mod-content/music.lib");
  return content_add_new_music($refers_to, $creation_user, $viewable_status, $license_name, $license_url, $one_line_summary, $description, $language, $filename, $mime_type, $temp_filename, $copyright_holder, $copyright_year, $source_url);
}

// Pre: $editor_id is the id of a user
//      $music_id is the id of a valid music object in the database
//      $new_status is either "approved,"rejected","expired", or "submitted"
// Post: Attempts to change the editorial status of the object to
// $editorial_status; returns true if successful, false otherwise.

function change_music_editorial_status($editor_id, $music_id, $new_status) {
  require_once("../modules/mod-content/music.lib");
  return content_change_music_editorial_status($editor_id, $music_id, $new_status);
}

// 
// SEARCH SECTION
//

// Pre: $query is a valid querystring
// Post: Searches for possible matches to the querystring and returns a set of search results. Search is for songs
// relating to query.
function searchContentSongs($query) {
  require_once("../modules/mod-content/content-search.lib");
  return content_searchContentSongs($query);
}

// Pre: $query is a valid query string
// Post: Returns a multi-row object of music objects that match the query

function search_music($query) {
  require_once("../modules/mod-content/music.lib");
  return content_search_music($query);
}

// Pre: $query is a valid querystring
// Post: Searches for possible matches to the querystring and returns a set of search results. Search is for information
// relating to query.
function searchContentInfo($query) {
  require_once("../modules/mod-content/content-search.lib");
  return content_searchContentInfo($query);
}

// Pre: $n is a number of content items to be returned.
// Post: Returns a string showing the $n most recent content items entered.
function getRecentContent($n) {
  require_once("../modules/mod-content/content-search.lib");
  return content_getRecentContent($n);
}

// Pre: $n is a number of content items to be returned.
// Post: Returns information about the $n most recent content items entered.
// This is meant for a SOAP remote procedure call.
function getRecentContentRPC($n) {
  require_once("../modules/mod-content/content-search.lib");
  return content_getRecentContentRPC($n);
}
