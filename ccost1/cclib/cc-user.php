<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCUser', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCUser', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCUser', 'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCUser' , 'OnGetConfigFields') );

// yes, the next two were meant to map to the same method...
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,   array( 'CCUsers', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_CONTEST_ROW,  array( 'CCUsers', 'OnUploadRow'));


class CCUserForm extends CCForm
{
    var $record;

    function CCUserForm()
    {
        $this->CCForm();
    }

    /**
     * Handles generation of &lt;input type='password' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_matchpassword($varname,$value='',$class='')
    {
        return( $this->generator_password($varname,$value,$class) );
    }

    function validator_matchpassword($fieldname)
    {
        if( !empty($this->record) )
        {
            $value =& $this->GetFormValue($fieldname);

            $password = md5( $value );

            if( $this->record['user_password'] != $password )
            {
                $this->SetFieldError($fieldname,"Password does not match login name.");
                return(false);
            }

            return( true );
        }

        return( false );
    }


    /**
     * Handles generation of &lt;input type='text' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_username($varname,$value='',$class='')
    {
        return( $this-> generator_textedit($varname,$value,$class) );
    }

    function validator_username($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);

            if( empty($value) )
                return(true);

            $users =& CCUsers::GetTable();
            $this->record = $users->GetRecordFromName( $value );

            if( empty($this->record) )
            {
                $this->SetFieldError($fieldname,"Can't find that username");
                return(false);
            }

            return( true );
        }

        return( false );
    }

}


class CCUserProfileForm extends CCUploadForm
{
    var $record;

    function CCUserProfileForm($userid,$upload_dir)
    {
        $this->CCUploadForm();
        $users =& CCUsers::GetTable();
        $this->record = $users->GetRecordFromID($userid);

        $fields = array( 
                    'user_real_name' =>
                        array( 'label'      => 'Full Name',
                               'form_tip'   => 'Your display name for the site (not to be confused with' .
                                                ' your login name).',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_password' =>
                       array( 'label'       => 'Password',
                               'formatter'  => 'password',
                               'flags'      => CCFF_SKIPIFNULL ),

                    'user_email' =>
                       array(  'label'      => 'e-mail',
                               'form_tip'   => 'This address will never show on the site but is '.
                                                'required for creating a new account and password '.
                                                'recovery in case you forget it.',
                               'formatter'  => 'email',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

                    'user_image' =>
                       array(  'label'      => 'Image',
                               'formatter'  => 'avatar',
                               'form_tip'   => 'Image file (can not be bigger than 93x93)',
                               'upload_dir' => $upload_dir,
                               'maxwidth'   => 93,
                               'maxheight'  => 94,
                               'flags'      => CCFF_POPULATE | CCFF_SKIPIFNULL  ),

                    'user_description' =>
                        array( 'label'      => 'About You',
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),

                    'user_homepage' =>
                       array(  'label'      => 'Home Page URL',
                               'form_tip'   => 'Make sure it starts with http://',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_whatido' =>
                        array( 'label'      => 'What I Pound On',
                               'form_tip'   => '(e.g. vinyl, guitar, ACID Pro, vocals, beat slicer)',
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_whatilike' =>
                        array( 'label'      => 'What I Like:',
                               'form_tip'   => '(e.g. Django, Old Skool, Miles Davis, Acid House)',
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),

                    'user_lookinfor' =>
                        array( 'label'      => "What I'm Looking For:",
                               'form_tip'   => "List attributes of musicians you'd like to hook up with ".
                                               '(e.g. Producer, singer, drummer)',
                               'formatter'  => 'tagsedit',
                               'flags'      => CCFF_POPULATE ),


                        );

        $this->AddFormFields( $fields );
        $this->SetSubmitText('Submit');
    }

}


class CCUsers extends CCTable
{
    function CCUsers()
    {
        global $CC_SQL_DATE;

        $this->CCTable( 'cc_tbl_user','user_id');
        $this->AddExtraColumn("DATE_FORMAT(user_registered, '$CC_SQL_DATE') as user_date_format");
    }

    /**
    * Returns static singleton of configs table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( empty($_table) )
            $_table = new CCUsers();
        return( $_table );
    }

    // -----------------------------------
    //  For turning vanilla db 'rows' into
    //        app-usable 'records'
    // ------------------------------------
    function & GetRecords($where)
    {
        $qr = $this->Query($where);
        $records = array();
        while( $row = mysql_fetch_assoc($qr) )
            $records[] = $this->GetRecordFromRow($row);

        return( $records );
    }

    function & GetRecordFromName($username)
    {
        $row = $this->QueryRow("user_name = '$username'");
        if( empty($row) )
            return(null);
        return( $this->GetRecordFromRow($row) );
    }

    function & GetRecordFromID($userid)
    {
        $row = $this->QueryKeyRow($userid);
        return( $this->GetRecordFromRow($row) );
    }

    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow(&$row)
    {
        $this->GetRecordFromRow($row,false);
    }

    function & GetRecordFromRow(&$row,$expand = true)
    {
        $row['artist_page_url']  = ccl('people' ,$row['user_name']);
        $row['user_emailurl']    = ccl('people' ,$row['user_name'],'contact');

        if( $row['user_image'] )
            $row['user_avatar_url'] = ccd( CCUser::GetUploadDir( $row ), $row['user_image'] );

        // todo: collapse these into the db
        $user_fields = array( 'Home Page' => 'user_homepage',
                              'About Me'  => 'user_description' );

        $row['user_fields'] = array();
        foreach( $user_fields as $name => $uf  )
        {
            if( empty($row[$uf]) )
                continue;
            $row['user_fields'][$uf] = array( 'label' => $name, 'value' => $row[$uf] );
        }

        if( $expand )
        {
            $row['user_tag_links'] = array();
            $tags =& CCTags::GetTable();
            $tags->ExpandOnRow($row,'user_favorites',ccl('tags'), 'user_tag_links',
                                    'Favorites' );
            $tags->ExpandOnRow($row,'user_whatilike',ccl('search/people', 'whatilike'), 'user_tag_links',
                                    'What I Like');
            $tags->ExpandOnRow($row,'user_whatido',  ccl('search/people', 'whatido'),'user_tag_links', 
                                    'What I Pound On');
            $tags->ExpandOnRow($row,'user_lookinfor',ccl('search/people', 'whatido'),'user_tag_links',
                                    'What I Look For');

            CCEvents::Invoke( CC_EVENT_USER_ROW, array( &$row ) );
        }

        return($row);
    }

}

class CCUser
{
    function IsLoggedIn()
    {
        global $CC_GLOBALS;

        return( !empty($CC_GLOBALS['user_name']) );
    }

    function IsAdmin()
    {
        if( !CCUtil::IsHTTP() )
            return(true);

        static $_admins;
        if( !isset($_admins) )
        {
            $configs =& CCConfigs::GetTable();
            $settings = $configs->GetConfig('settings');
            $_admins = $settings['admins'];
        }

        $name = CCUser::CurrentUserName();
        $ok = !empty($name) && (preg_match( "/(^|\W|,)$name(\W|,|$)/",$_admins) > 0);

        return( $ok );
    }

    function CurrentUser()
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? intval($CC_GLOBALS['user_id']) : -1 );
    }


    function CurrentUserName()
    {
        global $CC_GLOBALS;

        return( CCUser::IsLoggedIn() ? $CC_GLOBALS['user_name'] : '' );
    }

    function GetUserName($userid)
    {
        if( $userid == CCUser::CurrentUser() )
            return( CCUser::CurrentUserName() );

        $users =& CCUsers::GetTable();
        return( $users->QueryItemFromKey('user_name',$userid) );
    }

    function CheckCredentials($usernameorid)
    {
        $id     = CCUser::CurrentUser();
        $argid  = intval($usernameorid);
        $name   = CCUser::CurrentUserName();
        if( !$id || (($id !== $argid) && ($name != $usernameorid)) )
        {
           CCUtil::AccessError( __FILE__, __LINE__ );
        }
    }

    function IDFromName($username)
    {
        $users =& CCUsers::GetTable();
        $where['user_name'] = $username;
        return( $users->QueryKey($where) );
    }

    function EditProfile($username)
    {
        CCPage::SetTitle("Edit Your Settings");
        $upload_dir = $this->GetUploadDir($username);
        $id         = $this->IDFromName($username);
        $form       = new CCUserProfileForm($id, $upload_dir );
        $ok         = false;

        if( empty($_POST['userprofile']) )
        {
            $form->PopulateValues( $form->record );
        }
        else
        {
            if( $form->ValidateFields() )
            {
                $form->FinalizeAvatarUpload('user_image', $upload_dir);
                
                $password = $form->GetFormValue('user_password');
                if( !empty($password) )
                    $form->SetFormValue('user_password',md5($password));
    
                // todo: do we really want to add user tags to the tags tables??
                // $form->UpdateTagTables();

                $form->SetHiddenField('user_id', CCUser::CurrentUser() );
                $form->GetFormValues($fields);
                if( empty($fields['user_real_name']) )
                    $fields['user_real_name'] = $username;
                $users =& CCUsers::GetTable();
                $users->Update($fields);
                CCPage::Prompt("Changes were saved");
                $ok = true;
            }
        }

        if( !$ok )
            CCPage::AddForm( $form->GenerateForm() );
    }


    function ListRecords($sql_where = '')
    {
        $users =& CCUsers::GetTable();
        $records =& $users->GetRecords($sql_where);
        CCPage::PageArg( 'user_record', $records, 'user.xml/user_listing' );
    }

    function UserPage($username = '', $contact='')
    {
        if( empty($username) )
        {
            CCPage::SetTitle("People");
            $this->ListRecords();
        }
        else
        {
            $users    =& CCUsers::GetTable();
            $where['user_name'] = $username;
            $records  = $users->GetRecords($where);

            CCPage::SetTitle($records[0]['user_real_name']);
            CCPage::PageArg( 'user_record', $records, 'user.xml/user_listing' );
            $systags = $username == $this->CurrentUserName() ? CCUD_USER_UPLOADS : CCUD_GENERAL_UPLOADS;
            $uploads =& CCUploads::GetTable();
            if( $uploads->CountRows($where) > 0 )
            {
                CCUpload::ListMultipleFiles($where, $systags);
            }
            else
            {
                CCPage::ViewFile('welcome.xml');
            }
        }
    }

    function GetPeopleDir()
    {
        global $CC_GLOBALS;
        return( empty($CC_GLOBALS['user-upload-root']) ? 'people' : 
                            $CC_GLOBALS['user-upload-root'] );
    }

    function GetUploadDir($name_or_row)
    {
        if( is_array($name_or_row) )
            $name_or_row = $name_or_row['user_name'];

        return( CCUser::GetPeopleDir() . '/' . $name_or_row );
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
            $patterns['%artist%'] = "Artist name";
            $patterns['%login%']  = "Artist login name";
            $patterns['%page%']   = "Artist page URL";
        }
        else
        {
            $patterns['%artist%']        = $record['user_real_name'];
            $patterns['%login%']         = $record['user_name'];

            if( !empty($record['artist_page_url']) )
                $patterns['%artist_page%']   = $record['artist_page_url'];
        }
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $current_user_name = $this->CurrentUserName();

        $items = array( 
            'artists'   => array( 'menu_text'  => 'Browse Artists',
                             'menu_group' => 'visitor',
                             'weight' => 4,
                             'action' =>  ccl( 'people' ),
                             'access' => CC_DONT_CARE_LOGGED_IN
                             ),
                               
            'artist'   => array( 'menu_text'  => 'Your Page',
                             'menu_group' => 'artist',
                             'weight' => 10,
                             'action' =>  ccl( 'people' , $current_user_name ),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
                               
            'editprofile'  => array( 'menu_text'  => 'Edit Your Profile',
                             'menu_group' => 'artist',
                             'weight' => 11,
                             'action' =>  ccl( 'people' ,'profile',$current_user_name),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
                );

        CCMenu::AddItems($items);

        $groups = array(
                    'visitor' => array( 'group_name' => 'Visitors',
                                          'weight'    => 1 ),
                    'artist'  => array( 'group_name' => 'Artists',
                                          'weight'   => 2 )
                    );

        CCMenu::AddGroups($groups);

    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'people',         array('CCUser','UserPage'),     CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'people/profile', array('CCUser','EditProfile'),  CC_MUST_BE_LOGGED_IN );
    }

    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings to config editing form
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['admins'] =
               array(  'label'      => 'Site Administrators',
                       'form_tip'   => 'List login names of site admins.<br /> (e.g. admin, remixfight, sally)',
                       'value'      => 'Admin',
                       'formatter'  => 'tagsedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }

}
?>