<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: confirm-posting
// Purpose: To confirm that a user really wants to post a question.
// ----------------------------

require_once("../modules/mod-utilities/user-input.lib");
require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-utilities/redirect.lib");
require_once("../modules/mod-search/search-interface.php");
require_once("../modules/mod-external-libraries/nusoap/nusoap.php");

checkLoginStatus("in");

// Variables that must be set:
if (isset($_GET['subject']) && isset($_GET['body']) && isset($_GET['refers_to']) && isset($_GET['back_to_page'])) {
  $refers_to = urldecode($_GET['refers_to']);
  if (isForum($refers_to)) {
    $back_to_page = urldecode($_GET['back_to_page']);
    $subject = unstripTags(urldecode($_GET['subject']));
    $body = convertLineBreaks(unstripTags(unstripTags(urldecode($_GET['body']))));
    $author = currentUser();
    $authorlink = "user-profile?user_id=" . getUserID(currentUser());
    $date = formatDate($GLOBALS['current_date']);
    $thread = $subject;
    $forum_object = content_getForum($refers_to);
    $forum = createLink("forums-specific-forum?view_forum=" . $forum_object->content_id, unstripTags($forum_object->one_line_summary));
    $bgcolor = 'DDDDDD';
    $template_variables = compact("subject", "body", "author", "authorlink", "date", "thread", "subject", "bgcolor", "forum");
    $sample_posting = evalTemplate("mod-content/forum-posting.template", $template_variables);
    $body = unstripTags(unstripTags(urldecode($_GET['body'])));
    $replymessage = "Revise Posting (Choose Post Message Again to Submit)";
    $template_variables = array("subjectline" => $subject, "body" => $body, "replymessage" => $replymessage,
				"refers_to" => $refers_to, "confirmed" => $back_to_page);
    $edit_posting = evalTemplate("mod-content/forum-create-post.template", $template_variables);
    $query = stripAllTags($subject . ' ' . $body);
    $local_searches = searchFor($query);
      // Check out this next line - I am some sort of regular expression badass. - Matt
    $local_searches = ereg_replace('<A HREF="([^">]*)">', '<A HREF="\\1" target="seperate">', $local_searches);

    // GOOGLE SEARCH PART
    // NOTE - IF WE KEEP this in our code it should get put in its own module, but for now, for hte problem set, it can go here.

    $parameters = array(
      'key' => 'pvgFC/ZQFHLGi/72tpaKiVjakPD6tssp',
      'q' => $subject,
      'start' => '0',
      'maxResults' => '5');
    $soapclient = new soapclient('http://api.google.com/GoogleSearch.wsdl', 'wsdl');
    $results = $soapclient->call('doGoogleSearch', $parameters);
   
    // MATT - I should really clean this up later!!!
    $google_searches = "<TABLE CLASS=\"basic\">\n";
    $google_searches .= "<TR>\n";
    $google_searches .= " <TD><B>Google Results</B></TD>\n";
    $google_searches .= "</TR>\n";
       
    foreach ($results['resultElements'] as $result) {
	$google_searches .= "<TR BGCOLOR=EEEEEE>\n";
        $google_searches .= " <TD><A HREF=\"" . $result['URL'] . "\">" . $result['title'] . "</A><BR>\n";
        $google_searches .= "    " . $result['snippet'] . "</TD>";  
//        $google_searches .= $realkey . "=>" . $realvalue . "<BR>";
        $google_searches .= "</TR>\n";
    } 
    $google_searches .= "</TABLE>\n";

    $template_variables = compact("sample_posting", "edit_posting", "local_searches", "google_searches");
    generatePage("confirm-posting", "Confirm Question Posting", evalTemplate("confirm-posting.template", $template_variables));
  } else {
    redirectHeader("home?error=unknown");
  }
} else {
  redirectHeader("home?error=unknown");
}