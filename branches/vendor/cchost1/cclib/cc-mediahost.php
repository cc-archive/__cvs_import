<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,   array( 'CCMediaHost',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU, array( 'CCMediaHost',  'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,  array( 'CCMediaHost',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCMediaHost',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,  array( 'CCMediaHost',  'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCMediaHost' , 'OnGetConfigFields') );



class CCMediaHost
{
    /*-----------------------------
        MAPPED TO URLS
    -------------------------------*/

    function Media($username ='', $fileid = '')
    {
        if( empty($username) )
        {
            CCPage::SetTitle("Browse Uploads");
            CCUpload::ListMultipleFiles();
        }
        else
        {
            $uploads =& CCUploads::GetTable();
            $row = $uploads->QueryKeyRow($fileid);
            $row['works_page'] = true;
            $record = $uploads->GetRecordFromRow($row);
            CCPage::SetTitle($record['upload_name']);
            $arr = array(&$record);
            $record['local_menu'] = CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,$arr);
            CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, $arr );
            CCPage::PageArg( 'file_record', $arr, 'upload.xml/file_listing' );
        }
    }

    function Submit($username)
    {
        CCPage::SetTitle("Submit an Original Work");
        CCUser::CheckCredentials($username);
        $uid = CCUser::IDFromName($username);
        $form = new CCNewUploadForm($uid);
        
        $this->_add_publish_field($form);

        if( !empty($_POST['newupload']) )
        {
            if( $form->ValidateFields() )
            {
                $fileid = CCUpload::PostProcessUpload( $form, 
                                               array( CCUD_MEDIA_BLOG_UPLOAD, CCUD_ORIGINAL ),
                                               $this->_get_upload_dir($username) );

                if( $fileid )
                {
                    CCUpload::ListFile($username,$fileid);
                    return;
                }
            }
        }
        
        CCPage::AddForm( $form->GenerateForm() );
    }

    function Publish($username,$fileid)
    {
        $fileid = intval($fileid);
        $username = CCUtil::StripText($username);
        if( !CCUser::IsAdmin() )
            CCUpload::CheckFileAccess($username,$fileid);

        $uploads =& CCUploads::GetTable();
        $row = $uploads->QueryKeyRow($fileid);
        if( $row['upload_published'] )
            $value = 0;
        else
            $value = 1;
        $where['upload_published'] = $value;
        $where['upload_id'] = $fileid;
        $uploads->Update($where);
        
        CCUpload::ListFile( $username, $fileid );
        CCPage::SetTitle( $value ? "Published" : "Unpublished" );
    }

    function Remix( $remix_this_id = '' )
    {
        $username = CCUser::CurrentUserName();
        $userid = CCUser::CurrentUser();
        $form = new CCPostRemixForm($userid);
        $this->_add_publish_field($form);

        CCPage::SetTitle("Submit a Reply Remix");

        if( empty($_POST['postremix']) )
        {
            if( !empty( $remix_this_id ) )
            {
                $uploads =& CCUploads::GetTable();
                $record =& $uploads->GetRecordFromID($remix_this_id);
                $form->SetTemplateVar( 'remix_sources', array( $record ) );
                CCRemix::StrictestLicense($form);
            }

            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $upload_dir = $this->_get_upload_dir($username);

            CCRemix::OnPostRemixForm($form, $upload_dir,  array( CCUD_MEDIA_BLOG_UPLOAD, CCUD_REMIX ));
        }
    }


    /*-----------------------------
        HELPERS
    -------------------------------*/
    function _get_upload_dir($username)
    {
        global $CC_GLOBALS;
        $upload_root = empty($CC_GLOBALS['user-upload-root']) ? 'people' : 
                               $CC_GLOBALS['user-upload-root'];
        return( $upload_root . '/' . $username );
    }

    function _add_publish_field(&$form)
    {
        if( CCUser::IsAdmin() || $this->_is_auto_pub() )
        {
            $fields = array( 
                'upload_published' =>
                            array( 'label'      => 'Publish Now',
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_NONE,
                                   'value'      => 'on'
                            )
                        );
            
            $form->AddFormFields( $fields );

        }

    }

    function _is_auto_pub()
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        return( $settings['upload-auto-pub']  );
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
        if( CCUploads::InTags(CCUD_MEDIA_BLOG_UPLOAD,$record) )
        {
            $name = $record['upload_file_name'];
            $relative = $this->_get_upload_dir($record['user_name']);

            $record['relative_dir']  = $relative;
            $record['download_url']  = ccd( $relative, $name );
            $record['local_path']    = cca( $relative, $name );
            $record['file_page_url'] = ccl('media',$record['user_name'],$record['upload_id']) ;

            if( empty($record['upload_published']) )
            {
                $msg = 'This file is only visible to the owner and admins.';
                $record['publish_message'] = $msg;
                $record['file_macros'][] = 'upload.xml/upload_not_published';
            }

        }
    }

    /**
    * Event handler for getting renaming/id3 tagging macros
    *
    * @param array $record Record we're getting macros for (if null returns documentation)
    * @param array $patterns Substituion pattern to be used when renaming/tagging
    * @param array $mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros(&$record,&$patterns,&$mask)
    {
        if( empty($record) )
        {
            $patterns['%source_title%']  = "'Sampled from' title";
            $patterns['%source_artist%'] = "'Sampled from' artist";
            $patterns['%url%']           = 'Download URL';
            $patterns['%song_page%']     = 'File page URL';
            $mask['song']  = "Pattern to use for original works";
            $mask['remix'] = "Pattern to use for Remixes";
            return;
        }

        $configs =& CCConfigs::GetTable();
        $masks = $configs->GetConfig('name-masks');

        if( !CCUploads::InTags(CCUD_MEDIA_BLOG_UPLOAD,$record) )
            return;

        if( empty($record['remix_sources']) )
        {
            $patterns['%source_title%']  = 
            $patterns['%source_artist%'] = '';
        }
        else
        {
            $parent = $record['remix_sources'][0];
            $patterns['%source_title%'] = $parent['upload_name'];
            $patterns['%source_artist%'] = $parent['user_real_name'];
            if( empty($mask) )
                $mask = $masks['remix'];
        }

        if( empty($mask) )
            $mask = $masks['song'];

        if( !empty($record['download_url']) )
        {
            $patterns['%url%']       = $record['download_url'];
            $patterns['%song_page%'] = $record['file_page_url'];
        }
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $current_user_name = CCUser::CurrentUserName();

        $items = array( 
            'files'   => array(  'menu_text'  => 'Browse Media',
                                 'menu_group' => 'visitor',
                                 'weight'     => 4,
                                 'access'     => CC_DONT_CARE_LOGGED_IN,
                                 'action'     => ccl('media') ),
            'upload' => array(   'menu_text'  => 'Submit Original',
                                 'menu_group' => 'artist',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'weight'     => 6,
                                 'action'     => ccl('media','submit', $current_user_name) ),
            'remix' => array(    'menu_text'  => 'Post a Reply Remix',
                                 'menu_group' => 'artist',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'weight'     => 7,
                                 'action'     => ccl('media','remix') ),
                        );
        
        CCMenu::AddItems($items);

    }

    function OnUploadMenu(&$menu,&$record)
    {
        $isowner = CCUser::CurrentUser() == $record['user_id'];

        if( $isowner )
        {
            $menu['editupload'] = 
                         array(  'menu_text'  => 'Edit',
                                 'weight'     => 10,
                                 'id'         => 'editcommand',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'action'     => ccl('media','edit',
                                                    $record['user_name'],
                                                    $record['upload_id']) );

            $menu['deleteupload'] = 
                         array(  'menu_text'  => 'Delete',
                                 'weight'     => 11,
                                 'id'         => 'deletecommand',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'action'     => ccl( 'media', 'delete', $record['upload_id']) );
        }

        $ismediablog = CCUploads::InTags(CCUD_MEDIA_BLOG_UPLOAD,$record);

        if( $ismediablog && (($isowner && $this->_is_auto_pub()) || CCUser::IsAdmin()) )
        {
            if( $record['upload_published'] )
            {
                $classid = 'unpublishcommand';
                $text = 'Unpublish';
            }
            else
            {
                $classid = 'publishcommand';
                $text = 'Publish';
            }

            $menu['publish'] =
                    array( 'menu_text' => $text,
                           'weight'    => 50,
                           'id'        => $classid,
                           'access'    => CC_MUST_BE_LOGGED_IN,
                           'action'     => ccl( 'media', 'publish', 
                                                    $record['user_name'],
                                                    $record['upload_id']) );
        }

        $menu['downloadmedia'] = 
                 array(  'menu_text'  => 'Download',
                         'weight'     => 5,
                         'id'         => 'downloadcommand',
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => $record['download_url']);

        $menu['replyremix'] = 
                     array(  'menu_text'  => 'Post Reply Remix',
                             'weight'     => -5,
                             'id'         => 'replyremix',
                             'access'     => CC_MUST_BE_LOGGED_IN,
                             'action'     => ccl( 'media', 'remix', $record['upload_id']) );

    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'media',          array('CCMediaHost','Media'),    CC_DONT_CARE_LOGGED_IN);
        CCEvents::MapUrl( 'media/submit',   array('CCMediaHost','Submit'),   CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( 'media/remix',    array('CCMediaHost','Remix'),    CC_MUST_BE_LOGGED_IN);
        CCEvents::MapUrl( 'media/publish',  array('CCMediaHost','Publish'),  CC_MUST_BE_LOGGED_IN );
  
        CCEvents::MapUrl( 'media/edit',     array('CCUpload','Edit'),    CC_MUST_BE_LOGGED_IN );
        CCEvents::MapUrl( 'media/delete',   array('CCUpload','Delete'),  CC_MUST_BE_LOGGED_IN );
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
            $fields['user-upload-root'] =
               array( 'label'       => 'Media Upload Directory',
                       'form_tip'   => 'Files will be uploaded/downloaded here.(This must accessable from the Web.)',
                       'value'      => 'people',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
        else
        {
            $fields['upload-auto-pub'] =
                       array( 'label'       => 'Auto Publish Uploads',
                               'form_tip'   => 'Uncheck this if you want to verify uploads before they are made public',
                               'value'      => true,
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE );
        }

    }

}


?>