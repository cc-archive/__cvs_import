<?

// $Header$ 

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define('IN_PHPBB', true);

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,        array( 'CCPhpBB2',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,         array( 'CCPhpBB2',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPhpBB2',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPhpBB2' , 'OnGetConfigFields') );

// hack -- This has to be sync with your PHPBB2 version in includes/constants.php 
define('CC_POST_TOPIC_URL', 't');

define('CC_COMMENT_MENU_ITEM', 'commentcommand');


class CCPhpBB2
{
    function _init()
    {
        global $phpbb_root_path,$phpEx,$CC_GLOBALS;

        $phpbb_root_path = $CC_GLOBALS['phpbb2_root_path'];
        include($phpbb_root_path . 'extension.inc');
    }

    function SeeThread($file_id)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
        {
            CCPage::Prompt("phpBB2 integration is not enabled");
            return;
        }

        $this->_init();
        $uploads =& CCUploads::GetTable();
        $topic_id = $uploads->GetExtraField($file_id,'comment_topic_id');
        CCDatabase::DBClose();
        global $db,$userdata,$user_ip,$phpbb_root_path,$phpEx,$board_config;
        include('cclib/cc-phpbb2-cb.php');
        ccppbb_show_thread($topic_id,$CC_GLOBALS);
        exit;
    }

    function GetHeader($simple='')
    {
        global $CC_GLOBALS;

        header("Content-type: text/plain");
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $ttags = $configs->GetConfig('ttag');
        $cssurl = $ttags['root-url']  . '/' . $settings['style-sheet'];

        $head =<<<END
   <style>
     td.row1 { border-right: 1px #CCC solid; }
     td.row2 { border-right: 1px #CCC solid; }
     .spacerow { border-bottom: 1px #CCC solid; height: 17px;  }
   </style>
    <link rel="stylesheet" href="$cssurl" type="text/css" >
END;

        if( $simple )
        {
            $html = addslashes($head);
        }
        else
        {
            $args['macro_names'][] = 'page.xml/menu';
            $args['menu_groups'] = CCMenu::GetMenu();
            $template = new CCTemplate('cctemplates/comments.xml');
            $menu = $template->SetAllAndParse($args);
            $menu = preg_replace('/<!DOCTYPE[^>]*>\s*/','',$menu);

            $head .= <<<END
    <div class="cc_banner">
       <a class="cc_banner_link" id="cc_home_link" href="{$ttags['root-url']}" title="{$ttags['site-title']}">
         <span class="cc_site_title">{$ttags['site-title']} Forums</span>
       </a>
    </div>
    <div class="cc_site_description">{$ttags['site-description']}</div>
END;
        $html = addslashes($head . $menu);
        }

        $lines = split("\n",$html);
        foreach($lines as $line)
        {
            $line = trim($line);
            if( $line )
                print( " document.writeln('$line');\n ");
        }
        exit;
    }

    function PostComment($file_id)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
        {
            CCPage::Prompt("phpBB2 integration is not enabled");
            return;
        }

        $this->_init();
        global $db,$userdata,$user_ip,$phpbb_root_path,$phpEx,$board_config;
        $uploads =& CCUploads::GetTable();
        $topic_id = $uploads->GetExtraField($file_id,'comment_topic_id');
        if( !$topic_id )
        {
            $R = $uploads->GetRecordFromID($file_id);
            $menuitems = CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,array(&$R));
            foreach( $menuitems as $menuitem )
            {
                if( ($menuitem['id'] != CC_COMMENT_MENU_ITEM) && ($menuitem['access'] == CC_DONT_CARE_LOGGED_IN) )
                    $R['local_menu'][] = $menuitem;
            }
            $template = new CCTemplate($CC_GLOBALS['template-root'] . 'comments.xml');
            $args['macro_names'][] = 'comment_head';
            $args['record'] = $R;
            $html = $template->SetAllAndParse($args);
            $html = str_replace("\n"," ",$html);
            $html = str_replace("\r"," ",$html);

            CCDatabase::DBClose();

            include('cclib/cc-phpbb2-cb.php');

            $topic_id = ccppbb_post_new_thread( $R, $CC_GLOBALS, $html, "COMMENT/REVIEWS: " . $R['upload_name']);

            $uploads->SetExtraField($R,'comment_topic_id',$topic_id);
        }
        $url = $phpbb_root_path . "posting.$phpEx?mode=reply&" . CC_POST_TOPIC_URL . "=$topic_id";
        CCUtil::SendBrowserTo( append_sid( $url, true) );
        exit;
    }

    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow( &$record )
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
            return;

        if( !empty($record['works_page']) )
        {
            $record['file_macros'][] = 'comments.xml/comment_thread';
            $record['comment_thread_url'] = ccl( 'forum', 'thread', $record['upload_id'] );
        }
    }

    function OnUploadMenu(&$menu,&$record)
    {
        global $CC_GLOBALS;

        if( empty($CC_GLOBALS['phpbb2_enabled']) )
            return;

        global $phpbb_root_path, $phpEx;

        $this->_init();
        if( empty($record['upload_extra']['comment_topic_id']) )
        {
            $comment_url = ccl('forum','post', $record['upload_id']  );
            $text = 'Write Comment';
        }
        else
        {
            $topic_id    = $record['upload_extra']['comment_topic_id'];
            $comment_url = $phpbb_root_path . "viewtopic.$phpEx?" . CC_POST_TOPIC_URL . "=$topic_id";
            $text        = "Comments";
        }

        $menu['comments'] = 
                 array(  'menu_text'  => $text,
                         'weight'     => 12345,
                         'id'         => CC_COMMENT_MENU_ITEM,
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => $comment_url );
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'forum/post',        array( 'CCPhpBB2', 'PostComment'),  CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'forum/thread',      array( 'CCPhpBB2', 'SeeThread'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'forum/getheader',   array( 'CCPhpBB2', 'GetHeader'),    CC_DONT_CARE_LOGGED_IN);
    }

    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings to config editing form
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['phpbb2_enabled'] =
               array(  'label'      => 'phpbb2 Integration Enabled',
                       'form_tip'   => 'Enabled phpbb2',
                       'value'      => '1',
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_POPULATE );
            $fields['phpbb2_root_path'] =
               array(  'label'      => 'phpbb2 Directory',
                       'form_tip'   => 'Relative path to phpbb2 root directory (e.g. "phpBB2/")',
                       'value'      => 'phpBB2/',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
            $fields['phpbb2_forum_id'] =
               array(  'label'      => 'Comment Forum ID',
                       'form_tip'   => 'Forum ID number of where to put comments/reviews',
                       'value'      => '4',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
            $fields['phpbb2_admin_username'] =
               array(  'label'      => 'phpBB2 Admin Username',
                       'form_tip'   => 'Admin username to use for posting sticky comment',
                       'value'      => '2',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE );
        }
    }

}

?>