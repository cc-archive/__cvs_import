<?php

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

if( !defined('IN_PHPBB') )
    die('hacky hack');

include($phpbb_root_path . 'includes/bbcode.'.$phpEx);
include($phpbb_root_path . 'includes/functions_post.'.$phpEx);
include($phpbb_root_path . 'common.'.$phpEx);
if( CC_POST_TOPIC_URL != POST_TOPIC_URL )
    die("CC_POST_TOPIC_URL not initialized correctly");

function ccppbb_post_new_thread($R,$xconfig,$post_message,$post_subject)
{
    CCDebug::QuietErrors();

    global $db,$userdata,$user_ip,$phpbb_root_path,$phpEx,$board_config,$HTTP_COOKIE_VARS;

    // Login as user
    $old_userdata = session_pagestart($user_ip, PAGE_INDEX);
    $old_user_ip  = $user_ip;

    // we have to make up an IP to avoid flood controls
    // preventing us from posting two quick messages
    $user_ip      = encode_ip('255.255.0.255');

    // Fake Login as admin
	$sql = "SELECT * FROM " . USERS_TABLE . " WHERE username = '" . $xconfig['phpbb2_admin_username'] . "'";
	if ( !($result = $db->sql_query($sql)) )
	{
		message_die(CRITICAL_ERROR, 'Could not obtain admin data from user table', '', __LINE__, __FILE__, $sql);
	}

	$userdata = $db->sql_fetchrow($result);

    // We have to do this to get any language stuff to work
    init_userprefs($userdata);

    $mode = 'newtopic';
    $post_data = array( 'first_post' => 1,
                        'last_post' => '',
                        'has_poll' => '',
                        'edit_poll' => '',
                     );
    $forum_id     = $xconfig['phpbb2_forum_id'];
    $topic_type   = 0;
    $bbcode_on    = 0;
    $html_on      = 0;
    $smilies_on   = 0;
    $attach_sig   = 0;
    $post_message = addslashes($post_message);

    submit_post($mode, $post_data, $message, $meta, $forum_id, $topic_id, $post_id, $poll_id, $topic_type, $bbcode_on, $html_on, $smilies_on, $attach_sig, $B, $post_username, $post_subject, $post_message, $poll_title, $poll_options, $poll_length);

    update_post_stats($mode, $post_data, $forum_id, $topic_id, $post_id, $user_id);

    $userdata = $old_userdata;

    CCDebug::RestoreErrors();

    return( $topic_id );
}

function ccppbb_show_thread($topic_id,$xconfig,$limit = 10)
{
    CCDebug::QuietErrors();

    global $db,$userdata,$user_ip,$phpbb_root_path,$phpEx,$board_config;

    $userdata = session_pagestart($user_ip, PAGE_INDEX);
    init_userprefs($userdata);

	// Board is disabled, no topics are available
	if ($board_config['board_disable'])
	{
        $msg = $lang['Board_disable'];
        exit("document.writeln('$msg');\n");
	}

	// Get most important vars out parameters
	$error = false;
	$errorarray = array();

    $tt = TOPICS_TABLE;
    $ut = USERS_TABLE;
    $pt = POSTS_TABLE;
    $xt = POSTS_TEXT_TABLE;
    $au = $xconfig['phpbb2_admin_username'];

    $sql =<<<END
        SELECT post_time, username, post_text, bbcode_uid, enable_html
        FROM $pt p, $ut u, $xt txt
        WHERE poster_id = user_id AND
              txt.post_id = p.post_id AND
              p.topic_id = '$topic_id' AND
              username <> '$au'
        ORDER BY post_time DESC
END;

    if ( !($result = $db->sql_query($sql)) )
    {
        $error = true;
        ccppbb_output_die('General Error: Could not obtain topic information (topics sql)');
    }

    while( $row = $db->sql_fetchrow($result) )
    {
        ccppbb_compile_topic_text($row);
        $row['post_date_format'] = create_date($board_config['default_dateformat'], $row['post_time'], 
                                                        $board_config['board_timezone']);
        $topic_rowset[] = $row;
    }

    $err = mysql_error();

    $db->sql_freeresult($result);

    if( !empty($topic_rowset) )
    {
        global $CC_GLOBALS;

        $template = new CCTemplate( $CC_GLOBALS['template-root']  . 'comments.xml' );
        $args['macro_names'][] = 'comment_thread_list';
        $args['posts'] = $topic_rowset;
        $args['reply_topic_url'] = append_sid($phpbb_root_path . "posting.$phpEx?mode=reply&amp;" . POST_TOPIC_URL . "=$topic_id");

        $html = $template->SetAllAndParse($args,false,true);
        $sps = preg_split("/[\n\r]/",$html);
        foreach( $sps as $sp )
        {
            $sp = addslashes($sp);
            print( "document.write('$sp');\n" );
        }
    }

    if ($error)
	{
		ccppbb_output_die($errorarray);
	}

    CCDebug::RestoreErrors();
        
} 


function ccppbb_compile_topic_text(&$row)
{
    global $board_config;

	$message = $row['post_text'];
	$bbcode_uid = $row['bbcode_uid'];

	if ( !$board_config['allow_html'] )
	{
		if ( $row['enable_html'] )
		{
			$message = preg_replace('#(<)([\/]?.*?)(>)#is', "&lt;\\2&gt;", $message);
		}
	}

	if ( $board_config['allow_bbcode'] )
	{
		if ( $bbcode_uid != '' )
		{
			$message = ( $board_config['allow_bbcode'] ) ? bbencode_second_pass($message, $bbcode_uid) : preg_replace('/\:[0-9a-z\:]+\]/si', ']', $message);
		}
	}

    $row['post_text'] = $message;
}

function ccppbb_output_die($msg)
{
    exit("$msg;\n");
}


// EOF
?>