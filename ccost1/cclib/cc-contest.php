<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,     array( 'CCContest' , 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCContest' , 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_CONTEST_MENU,  array( 'CCContest' , 'OnContestMenu'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,    array( 'CCContest',  'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,    array( 'CCContest' , 'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCContest' , 'OnGetConfigFields' ));

/**
* Base class for contest creating/editing form
*
*/
class CCContestForm extends CCUploadForm
{
    /**
    * Constructor
    *
    */
    function CCContestForm()
    {
        $this->CCUploadForm();

        $username = CCUser::CurrentUserName();

        $fields = array(

            'contest_friendly_name' => array (
                        'label'      => 'Friendly Name',
                        'form_tip'   => 'This is the one people actually see',
                        'formatter'  => 'textedit',
                        'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

            'contest_description' => array (
                        'label'      => 'Description',
                        'form_tip'   => '',
                        'formatter'  => 'textarea',
                        'flags'      => CCFF_POPULATE),

            'contest_bitmap' => array (
                        'label'      => 'Logo',
                        'form_tip'   => 'Image file',
                        'formatter'  => 'avatar',
                        'flags'      => CCFF_POPULATE | CCFF_SKIPIFNULL ),

            'contest_publish' => array (
                        'label'      => 'Contest is Online',
                        'form_tip'   => 'Uncheck this to hide contest',
                        'formatter'  => 'checkbox',
                        'value'      => '1',
                        'flags'      => CCFF_POPULATE),
            
            'contest_deadline' => array (
                        'label'      => 'Contest Deadline',
                        'form_tip'   => 'Entries are not accepted after this date/time',
                        'formatter'  => 'date',
                        'value'      => 'now +2 weeks',
                        'flags'      => CCFF_POPULATE),
                
            'contest_auto_publish' => array (
                        'label'      => 'Auto-Publish Entries',
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'options'      => array( 
                                            '0' => 'Only admins can see entries until after the deadline',
                                            '1' => 'Entries are made public upon upload' ),
                        'value'      => '0',
                        'flags'      => CCFF_POPULATE),

            'contest_vote_online' => array (
                        'label'      => 'Vote Online',
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'options'      => array( 
                                            '0' => 'Winner is determined offline',
                                            '1' => 'Display a poll after deadline for entries has passed' ),
                        'value'      => '1',
                        'flags'      => CCFF_POPULATE),

            'contest_vote_deadline' => array (
                        'label'      => 'Voting Deadline',
                        'form_tip'   => 'When do polls close? (Only applies if you chose online voting above)',
                        'formatter'  => 'date',
                        'value'      => 'now +4 weeks',
                        'flags'      => CCFF_POPULATE),

            '_server_time' => array (
                        'label'      => 'Current Server Time',
                        'formatter'  => 'statictext',
                        'value'      => date('F d, Y h:i a'),
                        'flags'      => CCFF_NOUPDATE | CCFF_STATIC),

            );

        $this->AddFormFields( $fields );
        $this->SetHiddenField( 'contest_user', CCUser::CurrentUser() );
    }

}

/**
* Form used for creating contests
*
*/
class CCCreateContestForm extends CCContestForm
{
    /**
    * Constructor
    *
    */
    function CCCreateContestForm()
    {
        $this->CCContestForm();

        $fields = array(
                'contest_short_name' => array (
                        'label'      => 'Internal Name',
                        'form_tip'   => 'Letters and numbers only, 25 or less',
                        'formatter'  => 'shortcontestname',
                        'class'      => 'cc_form_input_short',
                        'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
            );

        $this->AddFormFields( $fields );

        $this->SetHelpText("After you complete this form you'll be prompted to upload files for the contest.");
    }

    /**
    * Special HTML generator for short (internal) contest names
    *
    * This method is called from CCForm, don't call it.
    *
    * @see CCForm::GenerateForm
    * @param string $varname Name of form field
    * @param string $value Value (if any) to populate into form field
    * @param string $class CSS class to use for this field
    * @returns string HTML that represents form field
    */
    function generator_shortcontestname($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }
    
    /**
    * Special POST validator for short (internal) contest name.
    *
    * Validates the short (internal) contest name given by users making
    * it adheres to very strict rules about what the internal name will
    * look like. This is basically done because the name will be used
    * as a directory name and in URLs so we want to keep the name
    * very, very simple and small
    *
    * This method is called from CCForm, don't call it
    * 
    * @see CCForm::ValidateFields
    * @param string $fieldname Name of form field
    * @returns bool $ok true if field validates ok
    */
    function validator_shortcontestname($fieldname)
    {
        if( $this->GetFormFieldItem($fieldname,'flags') & CCFF_HIDDEN )
            return(true);

        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);
            if( preg_match('/[^a-z0-9]/i',$value) || (strlen($value) > 25) )
            {
                $this->SetFieldError($fieldname, 'Must be characters and numbers, no more than 25');
                return(false);
            }
            $contests =& CCContests::GetTable();
            if( $contests->GetIDFromShortName($value) )
            {
                $this->SetFieldError($fieldname, 'Name already exists');
                return(false);
            }

            return(true);
        }

        return(false);
    }
}

/**
* Form used for editing contest information
*
*/
class CCEditContestForm extends CCContestForm
{
    /**
    * Constructor
    *
    * @param array $R Database record of contest 
    * @param string $upload_dir Directory where avatars will be put
    */
    function CCEditContestForm($R,$upload_dir)
    {
        $this->CCContestForm();
        $this->SetSubmitText('Submit Contest Changes');
        $this->SetFormFieldItem( 'contest_bitmap',    'upload_dir', $upload_dir );
        $this->SetHiddenField('contest_id',$R['contest_id']);
    }
}

/**
* Form used for uploading Contest source files
*
*/
class CCUploadContestSourceForm extends CCNewUploadForm
{
    /**
    * Constructor
    *
    * @param array $R Database record of contest this source is for
    */
    function CCUploadContestSourceForm( $R )
    {
        // $R['contest_user']
        $this->CCNewUploadForm( CCUser::CurrentUser() );
        $fields = array(
                'ccud_tags' => array(
                        'label'      => 'Source Type:',
                        'form_tip'   => '',
                        'formatter'  => 'radio',
                        'value'      => CCUD_CONTEST_MAIN_SOURCE,
                        'options'    => array( 
                                           CCUD_CONTEST_MAIN_SOURCE
                                                           => "Main Remix Material",
                                           CCUD_CONTEST_SAMPLE_SOURCE
                                                           => "Sample (loop, fragment, etc.)"),
                        'flags'      => CCFF_NOUPDATE),
            );
        $this->AddFormFields($fields);
        $this->SetHelpText( "Use this form to upload the sources for the remix contest");
        $this->SetHiddenField("upload_contest",$R['contest_id']);
    }

}

/**
* Form used for uploading a contest entry
*
*/
class CCSubmitContestEntryForm extends CCPostRemixForm
{
    /**
    * Constructor
    *
    * @param array $R Database record of contest this entry is for
    */
    function CCSubmitContestEntryForm($R)
    {
        // $R['contest_user']
        $this->CCPostRemixForm( CCUser::CurrentUser() );
        $this->SetHiddenField("upload_contest",$R['contest_id']);
        $this->SetTemplateVar('remix_search', true);
   }
}


//-------------------------------------------------------------------

/**
* Wrapper for database Contest table
*
*/
class CCContests extends CCTable
{
    var $_publish_filter;

    /**
    * Constructor -- don't use new, use GetTable() instead
    *
    * @see CCTable::GetTable
    */
    function CCContests()
    {
        $this->CCTable('cc_tbl_contests','contest_id');
        $this->AddJoin(new CCUsers(),'contest_user');

        if( !CCUser::IsAdmin() )
            $_publish_filter = '(contest_publish > 0)';
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
        if( !isset($_table) )
            $_table = new CCContests();
        return($_table);
    }

    /**
    * Returns the display name for a contest given the short (internal) name
    *
    * @param string $name Short (internal) name of contest
    * @return string $long_name Display (friendly) name of contest
    */
    function GetFriendlyNameFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        return( $this->QueryItem( 'contest_friendly_name', $where ) );
    }

    /**
    * Returns the database ID for a contest given the short (internal) name
    *
    * @param string $name Short (internal) name of contest
    * @return integer $id Database ID of contest
    */
    function GetIDFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        return( $this->QueryItem( 'contest_id', $where ) );
    }

    /**
    * Returns the display ready record for a contest given the short (internal) name
    *
    * This will return a full record (as opposed to raw database row) for a contest
    *
    * @param string $name Short (internal) name of contest
    * @return array $record Full record (as opposed to raw db row) for contest
    */
    function & GetRecordFromShortName($name)
    {
        $where['contest_short_name'] = $name;
        $row = $this->QueryRow($where);
        return( $this->GetRecordFromRow($row) );
    }

    /**
    * Returns a series of full display ready records of contests
    *
    * @see CCTable::GetRecordFromRow
    * @see CCTable::Query
    * @param mixed $where array or string specifying row filter
    * @param bool  $expand true if you need the local command menus for each row
    * @param integer $offset Offset into database
    * @param integer $limit Number of records to return
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetRecords($where='',$expand=true, $offset=0, $limit=0)
    {
        $this->SetOffsetAndLimit($offset,$limit);
        $qr = $this->Query($where);
        $this->SetOffsetAndLimit(0,0);
        $records = array();
        while( $row = mysql_fetch_assoc($qr) )
        {
            $record = $this->GetRecordFromRow($row,$expand);
            $records[] = $record;
        }

        return( $records );
    }

    /**
    * Returns the polling data for the most current contest
    *
    * This method returns a complex array of polling data and other contest
    * information. Tip: dump the return value of this method using CCDebug::PrintVar()
    * to exactly what is returned.
    *
    * Also note: the return values change slightly depending on whether the current
    * user has already voted in the poll. If not, the data strunction includes
    * the URL of the vote command as well as text to use for a link
    *
    * @returns array $poll_info A complex structure of polling data for the most current contest
    */
    function & GetCurrentPollingData()
    {   
        $where = "(contest_vote_online > 0) AND (contest_deadline < NOW()) AND (contest_vote_deadline > NOW())";
        $this->SetSort('contest_vote_deadline','DESC');
        $records = $this->GetRecords($where,false,0,1);
        
        if( empty($records) )
            return(null);

        $record = $records[0];
        $polls =& CCPolls::GetTable();
        $data = $polls->GetPollingData($record['contest_short_name'],'poll_numvotes');
        $data['contest_friendly_name'] = $record['contest_friendly_name'];
        if( $this->OKToVote($record) )
        {
            $data['contest_vote_url'] = ccl('contest', $record['contest_short_name'], 'vote' );
            $data['contest_vote_text'] = 'Vote';
        }

        return($data);
    }

    /**
    * Returns the display ready records for the currently open contests
    *

    * @see CCTable::GetRecordForRow
    * @param bool  $expand true if you need the local command menus for each row
    * @param integer $limit Number of contests to return
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetOpenContests($expand=false,$limit=0)
    {
        $where = "(contest_deadline > NOW()) OR (contest_vote_deadline > NOW())";
        return( $this->GetRecords($where,$expand,0,$limit) );
    }

    /**
    * Returns the display ready records for contests no longer open
    *
    * @see CCTable::GetRecordForRow
    * @param bool  $expand true if you need the local command menus for each row
    * @returns array $records Array of display ready records from Contests table
    */
    function & GetPastContests($expand=false)
    {
        $where = "(contest_deadline < NOW()) AND (contest_vote_deadline < NOW())";
        return( $this->GetRecords($where,$expand) );
    }

    /**
    * Populate a database row for a contest with specific state flags
    *
    * Upon returns there will be several boolean flags regarding the
    * current state of this contest. 
    *
    * <code>

        $a['contest_taking_submissions']  // true if NOW is before deadline
        $a['contest_voting_open']         // true if voting is allowed and NOW is after deadline but before voting deadline
        $a['contest_show_results']        // true if voting is allowed and after voting deadline
        $a['contest_can_browse_entries']  // true if browing is always allowed or after contest deadline

    *  </code>
    *
    * @param array $row Reference to contest database row
    */
    function GetOpenStatus(&$row)
    {
        $row['contest_taking_submissions'] = false;
        $row['contest_voting_open']        = false;
        $row['contest_show_results']       = false;
        $row['contest_can_browse_entries'] = false;
        if( $row['contest_publish'] > 0 )
        {
            $deadline = strtotime($row['contest_deadline']);
            $now      = time();
            if( $deadline > $now )
            {
                $row['contest_taking_submissions'] = true;

                if( $row['contest_auto_publish'] )
                    $row['contest_can_browse_entries'] = true;
            }
            else
            {
                $row['contest_can_browse_entries'] = true;

                if( $row['contest_vote_online'] )
                {
                    $deadline = strtotime($row['contest_vote_deadline']);
                    $row['contest_show_results'] = true;
                    if( $deadline > $now )
                        $row['contest_voting_open'] = true;
                }
            }
        }

    }

    /**
    *  Converts a raw database row to a semantically rich (display ready) record
    *
    * @param array $row Reference to database row
    * @param bool  $expand true if you want to include local menu commands for each record
    */
    function & GetRecordFromRow(&$row,$expand = true)
    {
        if( !$row['contest_id'] )
            return;

        $this->GetOpenStatus($row);

        $row['contest_url']               = ccl('contest', $row['contest_short_name'] );
        $row['contest_rules_text']        = ''; // nl2br(file_get_contents($row['contest_rules_file']));
        $row['contest-homepage']          = false;

        if( $row['contest_bitmap'] )
        {
            $relative = CCContest::_get_upload_dir($row);
            $row['contest_bitmap_url'] =  ccd( $relative, $row['contest_bitmap'] );
        }

        $row['contest_deadline_fmt']      = date(' l F jS, Y \a\t g:ha',
                                              strtotime($row['contest_deadline']));
        $row['contest_vote_deadline_fmt'] = date(' l F jS, Y \a\t g:ha',
                                              strtotime($row['contest_vote_deadline']));

        if( $row['contest_taking_submissions'] )
        {
            if( !CCUser::IsLoggedIn() )
            {
                $login = "<br /><br />(Only logged in users can submit entries.) <br />";
            }
            else
            {
                $login = '';
            }
            $row['contest_states'][] = 
                array( 'css_class' => "cc_contest_open",
                       'text'      => $row['contest_friendly_name'] . 
                                      ' is currently open and taking submissions.<br /> ' .
                                      'Submissions allowed until: ' .
                                      "<br /><br />" .
                                      $row['contest_deadline_fmt'] . $login
                     );
        }
        else
        {
            $row['contest_states'][] = 
                array( 'css_class' => "cc_contest_closed",
                       'text'      => $row['contest_friendly_name'] . 
                                      ' is not taking submissions any more.<br /> ' .
                                      'Submissions stopped after: ' .
                                      $row['contest_deadline_fmt']
                     );
        }

        if( $row['contest_voting_open'] )
        {
            $row['contest_states'][] = 
                array( 'css_class' => "cc_contest_voting_status",
                       'text'      => 'Voting is open until ' .
                                      $row['contest_vote_deadline_fmt']
                     );
        }

        if( $expand )
        {
            $row['local_menu'] =& CCMenu::GetLocalMenu(CC_EVENT_CONTEST_MENU,array(&$row));
            CCEvents::Invoke(CC_EVENT_CONTEST_ROW,array(&$row));
        }

        return( $row );
    }

    /**
    * Verifies that the current user is allowed to vote in the current contest
    *
    */ 
    function OKToVote(&$record)
    {
        return( empty($_REQUEST['polls']) && CCUser::IsLoggedIn() && 
                   $record['contest_voting_open'] && 
                  !CCPoll::AlreadyVoted($record['contest_short_name']) );
    }

    /**
    * Overwrites base class to add specific publishing and other filters
    *
    */
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);

        if( !empty($this->_publish_filter) )
        {
            if( empty($where) )
                $where = $this->_publish_filter;
            else
                $where = "($where) AND ({$this->_publish_filter})";
        }
        $sql = parent::_get_select($where,$columns);
        return($sql);

    }

}

//-------------------------------------------------------------------

/**
* Contest API and event callbacks
*
*/
class CCContest
{
    /**
    * Handler for contest/create
    *
    * Show a contest create form and handles POST
    */
    function CreateContest()
    {
        CCPage::SetTitle("Create Contest");

        $ok = false;
        $form = new CCCreateContestForm();
        if( !empty($_POST['createcontest']) )
        {
            $ok = $form->ValidateFields();

            if( $ok )
            {
                $contest_short_name = $form->GetFormValue('contest_short_name');

                $upload_dir = $this->_get_upload_dir($contest_short_name);
                $form->FinalizeAvatarUpload('contest_bitmap', $upload_dir);

                $form->SetHiddenField('contest_created', date('Y-m-d H:i') );
                $form->GetFormValues($fields);
                $contests =& CCContests::GetTable();
                $contests->Insert($fields);
                CCUtil::SendBrowserTo( ccl('contest','newsource',$contest_short_name) );
            }
        }

        if( !$ok )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Displays contest listing into the current page
    *
    * @param string $contest_short_name Optional: if this parameter is not null only one contest will be displayed, otherwise all contests.
    */
    function ViewContests($contest_short_name='')
    {
        $contests =& CCContests::GetTable();

        if( empty($contest_short_name) )
        {
            $records =& $contests->GetRecords();
        }
        else
        {
            $contests =& CCContests::GetTable();
            $record = $contests->GetRecordFromShortName($contest_short_name);
            CCPage::SetTitle($record['contest_friendly_name']);
            $record['contest-homepage'] = true;
            $records = array( &$record );
        }

        CCPage::PageArg( 'contest_record', $records, 'contest.xml/contest_listing' );
    }

    /**
    * List files owned by a given user that has 'contest' tags
    *
    */
    function Media( $username, $fileid )
    {
        CCUpload::ListFile($username,$fileid, CCUD_CONTEST_ALL);
    }

    /**
    * Display latest open contest 
    *
    */
    function CurrentContest()
    {
        $contests =& CCContests::GetTable();
        $contests->SetSort('contest_deadline', 'DESC');
        $rows =& $contests->GetOpenContests(true,1);
        if( empty($rows) )
        {
            CCPage::Prompt("There are no contests currently open");
        }
        else
        {
            $record = $rows[0];
            CCPage::SetTitle($record['contest_friendly_name']);
            $record['contest-homepage'] = true;
            $records = array( &$record );
            CCPage::PageArg( 'contest_record', $records, 'contest.xml/contest_listing' );
        }
    }

    /**
    * Catch all and dispatcher for many contest realted urls
    *
    * handles the following urls:
    * <code>
        //     contest
        //     contest/[name]
        //     contest/[name]/edit
        //     contest/[name]/submit
        //     contest/[name]/sources
        //     contest/[name]/sources/submit
        //     contest/[name]/entries
        //     contest/[name]/vote
        //     contest/[name]/results
        //     contest/[name]/delete
    * </code>
    *
    * @param string $contest_short_name Short (internal) name of contest
    * @param string $op1 Next part of url
    * @param string $op2 Last part of url
    */
    function Contests($contest_short_name='', $op1='', $op2='')
    {
        switch( $op1 )
        {
            case 'edit':
                $this->EditContest($contest_short_name);
                break;

            case 'submit':
                $this->SubmitEntry($contest_short_name);
                break;

            case 'sources':
                if( $op2 == 'submit' )
                {
                    $this->SubmitSource($contest_short_name);
                }
                else
                {
                    $contests =& CCContests::GetTable();
                    $longname = $contests->GetFriendlyNameFromShortName($contest_short_name) ;
                    CCPage::SetTitle( "Download Sources for '$longname'" );
                    $this->_list_contest_uploads($contest_short_name,CCUD_CONTEST_ALL_SOURCES);
                }
                break;

            case 'entries':
                $contests =& CCContests::GetTable();
                $longname = $contests->GetFriendlyNameFromShortName($contest_short_name) ;
                CCPage::SetTitle( "Entries in '$longname'" );
                $this->_list_contest_uploads($contest_short_name,CCUD_CONTEST_ENTRY);
                break;

            case 'vote':
                $this->Vote($contest_short_name);
                break;

            case 'results':
                $this->VoteResults($contest_short_name);
                break;

            case 'delete':
                break;

            default:
                CCPage::SetTitle("Browse Contests");
                $this->ViewContests($contest_short_name);
        }
    }

    /**
    * Handles contest/[name]/edit and POST results from form
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function EditContest($contest_short_name)
    {
        $contests =& CCContests::GetTable();

        $record = $contests->GetRecordFromShortName($contest_short_name) ;
        CCPage::SetTitle("Edit " . $record['contest_friendly_name']);

        $upload_dir = $this->_get_upload_dir($contest_short_name);
        $form = new CCEditContestForm($record,$upload_dir);
        $show = true;
        if( !empty($_POST['editcontest']) )
        {
            if( $form->ValidateFields() )
            {
                $upload_dir = $this->_get_upload_dir($contest_short_name);
                $form->FinalizeAvatarUpload('contest_bitmap', $upload_dir);
                $form->GetFormValues($fields);
                $contests =& CCContests::GetTable();
                $contests->Update($fields);
                CCPage::Prompt("Changes Saved");
                $this->ViewContests($contest_short_name);
                $show = false;
            }
        }
        else
        {
            $form->PopulateValues($record);
        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Handles contest/[name]/vote 
    *
    * This will actually generate a poll for the contest if one
    * doesn't exists so I guess it's a hole in the architecture
    * if someone pings the url directly for a contest that 
    * isn't supposed to get a poll.
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function Vote($contest_short_name)
    {
        $polls    =& CCPolls::GetTable();
        if( !$polls->PollExists($contest_short_name) )
        {
            $entries =& $this->_contest_uploads($contest_short_name,CCUD_CONTEST_ENTRY);
            $pollinsert = array();
            foreach( $entries as $entry )
            {
                $pollinsert[] = array( $contest_short_name,
                                       $entry['user_real_name'] . '/' . $entry['upload_name'] );
            }
            $columns = array( 'poll_id', 'poll_value' );
            $polls->InsertBatch($columns, $pollinsert);
        }
        $contests =& CCContests::GetTable();
        $where['contest_short_name'] = $contest_short_name;
        $vote_expires = $contests->QueryItem('contest_vote_deadline',$where);
        $form = new CCPollsForm($contest_short_name,strtotime($vote_expires));
        $form->SetHandler( ccl('contest', 'poll', 'results', $contest_short_name) );
        CCPage::AddForm( $form->GenerateForm() );
        $longname = $contests->GetFriendlyNameFromShortName($contest_short_name);
        CCPage::SetTitle("Poll for $longname");
    }

    /**
    * Handles contest/[name]/vote POST from form
    *
    * This will add a vote to the poll (if allowed) and display the new results
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function PollResults($contest_short_name)
    {
        CCPoll::Vote($contest_short_name);
        $this->VoteResults($contest_short_name);
    }

    /**
    * Handles contest/[name]/poll/results 
    *
    * This will display the results of the contest's poll
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function VoteResults($contest_short_name)
    {
        $polls =& CCPolls::GetTable();
        $data = $polls->GetPollingData($contest_short_name,'poll_numvotes');
        $contests =& CCContests::GetTable();
        $record = $contests->GetRecordFromShortName($contest_short_name);
        $data = array_merge($data,$record);
        CCPage::PageArg('poll_data',$data, 'contest.xml/polling_data');
        CCPage::SetTitle("Poll Results for {$record['contest_friendly_name']}");
    }

    /**
    * Handles contest/[name]/entry/submit
    *
    * This will display a form to submit an entry to the contest.
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function SubmitEntry($contest_short_name)
    {
        $contests =& CCContests::GetTable();
        $record = $contests->GetRecordFromShortName($contest_short_name) ;
        CCPage::SetTitle("Submit Entry for '{$record['contest_friendly_name']}'");
        $form = new CCSubmitContestEntryForm($record);
        $show = true;
        if( empty($_POST['submitcontestentry']) )
        {
            $records = $this->_contest_uploads($contest_short_name,CCUD_CONTEST_MAIN_SOURCE);
            $form->SetTemplateVar( 'remix_sources', $records );
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $remixapi = new CCRemix();
            $ccud = array( CCUD_CONTEST_ENTRY,
                           $contest_short_name );
            $upload_dir = $this->_get_user_upload_dir($record,CCUser::CurrentUserName());
            $remixapi->OnPostRemixForm($form,$upload_dir,$ccud);
        }
    }

    /**
    * Handles contest/[name]/entry/submit
    *
    * This will display a form to submit sources to the contest.
    *
    * @param string $contest_short_name Short (internal) name of contest
    */
    function SubmitSource($contest_short_name)
    {
        $contests =& CCContests::GetTable();
        $record =& $contests->GetRecordFromShortName($contest_short_name);
        CCPage::SetTitle( "Submit Sources for '{$record['contest_friendly_name']}'" );
        $form = new CCUploadContestSourceForm($record);
        $show = true;
        if( !empty($_POST['uploadcontestsource']) )
        {
            CCUser::CheckCredentials($_POST['upload_user']);

            if( $form->ValidateFields() )
            {
                $ccud = array( $record['contest_short_name'],
                                $form->GetFormValue('ccud_tags') );                                

                $upload_dir = $this->_get_upload_dir($record);

                $id = CCUpload::PostProcessUpload($form,$ccud,$upload_dir);

                if( $id )
                {
                    $this->_list_contest_uploads($contest_short_name,$ccud,$id);
                    $show = false;
                }
            }
        }
        
        if( $show )
            CCPage::AddForm( $form->GenerateForm() );
    }

    /* ------------------------------
        Class helpers
       ------------------------------ */
    /**
    * Internal helper method
    *
    * Returns the all the uploads for a given contest, filtered by type
    *
    * @param string $contest_short_name Short (internal) name of contest
    * @param string $systag System tag to filter on
    * @param integer $fileid Get only this one file
    * @returns array $records Records based on parameter requests
    */
    function & _contest_uploads($contest_short_name,$systags,$fileid=0)
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetSort( 'upload_date', 'DESC' );
        $uploads->SetTagFilter($systags);
        $where['contest_short_name'] = $contest_short_name;
        if( $fileid )
            $where['upload_id'] = $fileid;
        $records =& $uploads->GetRecords($where);
        return($records);
    }

    /**
    * Internal helper method
    *
    * Displays the all the uploads for a given contest, filtered by type
    *
    * @param string $contest_short_name Short (internal) name of contest
    * @param string $systag System tag to filter on
    * @param integer $fileid Get only this one file
    */
    function _list_contest_uploads($contest_short_name,$systags,$fileid=0)
    {
        $records =& $this->_contest_uploads($contest_short_name,$systags,$fileid);
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $menu = CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,array(&$records[$i]));
            $records[$i]['local_menu'] = $menu;
        }

        CCPage::PageArg( 'file_record', $records, 'upload.xml/file_listing' );
    }

    /**
    * Internal helper method
    *
    * Returns the upload directory associated with the username (or one
    * found in the record
    *
    * @param array $record Database record with contest name in it
    * @param string $username For this user (if blank, $record is assumed to have a username in it)
    * @returns string $dir User's uplaod directory for this contest
    */
    function _get_user_upload_dir( $record, $username='' )
    {
        $basedir = $this->_get_upload_dir($record);
        if( empty($username) )
            $username = $record['user_name'];

        return( $basedir . '/' . $username );
    }

    /**
    * Internal helper method
    *
    * Returns the base upload directory associated with a contest
    *
    * @param mixter $name_or_row Either short contest name or database record with contest name in it
    */
    function _get_upload_dir($name_or_row)
    {
        global $CC_GLOBALS;

        if( is_array($name_or_row) )
            $name_or_row = $name_or_row['contest_short_name'];

        $base_dir = empty($CC_GLOBALS['contest-upload-root']) ? 'contests' : 
                            $CC_GLOBALS['contest-upload-root'];

        return( $base_dir . '/' . $name_or_row );
    }


    /**
    * Event handler for building menus  
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array (
            'addcontest'  => array( 'menu_text'  => 'Create Contest',
                             'menu_group' => 'contests',
                             'weight' => 100,
                             'action' =>  ccl('contest', 'create'),
                             'access' => CC_ADMIN_ONLY
                              ),
             );

        $groups = array(
                    'contests' => array( 'group_name' => 'Contests',
                                          'weight'    => 4 ),
                    );

        CCMenu::AddGroups($groups);
        CCMenu::AddItems($items);
    }

    /**
    * Event handler for building local menus for contest rows
    *
    * @see CCMenu::AddItems
    */
    function OnContestMenu(&$menu,&$record)
    {
        $contest = $record['contest_short_name'];
        
        $menu['getcontestsources'] = 
                 array(  'menu_text'  => 'Download Source Material',
                         'weight'     => 1,
                         'id'         => 'downloadcommand',
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => ccl( 'contest', $contest, 'sources' ) );

        $menu['editcontest'] = 
                     array(  'menu_text'  => 'Edit',
                             'weight'     => 100,
                             'id'         => 'editcommand',
                             'access'     => CC_ADMIN_ONLY,
                             'action'     => ccl('contest',  $contest, 'edit') );

        $menu['contestsubmitsources'] = 
                     array(  'menu_text'  => 'Submit Sources',
                             'weight'     => 110,
                             'id'         => 'submitsourcescommand',
                             'access'     => CC_ADMIN_ONLY,
                             'action'     => ccl('contest', $contest, 'sources', 'submit' ));

        $menu['deletecontest'] = 
                     array(  'menu_text'  => 'Delete',
                             'weight'     => 120,
                             'id'         => 'deletecommand',
                             'access'     => CC_ADMIN_ONLY,
                             'action'     => ccl( 'contest', $contest, 'delete' ) );


        if( CCContests::OKToVote($record) )
        {
            $menu['contestvote'] = 
                         array(  'menu_text'  => 'Vote',
                                 'weight'     => 15,
                                 'id'         => 'votecommand',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'action'     => ccl('contest', $contest, 'vote' ));
        }
        elseif( $record['contest_show_results'] )
        {
            $menu['contestresults'] = 
                         array(  'menu_text'  => 'Results',
                                 'weight'     => 15,
                                 'id'         => 'votecommand',
                                 'access'     => CC_DONT_CARE_LOGGED_IN,
                                 'action'     => ccl('contest', $contest, 'results' ));
        }

        if( $record['contest_taking_submissions'] )
        {
            $menu['contestsubmit'] = 
                         array(  'menu_text'  => 'Submit Entry',
                                 'weight'     => 10,
                                 'id'         => 'submitcommand',
                                 'access'     => CC_MUST_BE_LOGGED_IN,
                                 'action'     => ccl('contest', $contest, 'submit' ));
        }

        if( CCUser::IsAdmin() || $record['contest_can_browse_entries'] )
        {
            $menu['viewcontestentries'] = 
                     array(  'menu_text'  => 'Browse Entries',
                             'weight'     => 20,
                             'id'         => 'browsecommand',
                             'access'     => CC_DONT_CARE_LOGGED_IN,
                             'action'     => ccl( 'contest', $contest, 'entries' ) );
        }

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
        if( empty($record['upload_contest']) )
            return;

        $systags = CCUploads::SplitTags($record);

        $isentry  = in_array( CCUD_CONTEST_ENTRY, $systags);
        $issource = !$isentry && (in_array( CCUD_CONTEST_MAIN_SOURCE, $systags ) ||
                                 in_array( CCUD_CONTEST_SAMPLE_SOURCE, $systags) );

        if( $issource  )
            $relative = $this->_get_upload_dir($record);
        elseif( $isentry )
            $relative = $this->_get_user_upload_dir($record);
        else
            return;
        
        if( $isentry )
        {
            CCContests::GetOpenStatus(&$record);
            if( !$record['contest_can_browse_entries'] )
            {
                $msg = 'This contest entry is only visible to the owner and admins.';
                $record['publish_message'] = $msg;
                $record['file_macros'][] = 'upload.xml/upload_not_published';
            }
        }

        $record['relative_dir']  = $relative;
        $record['download_url']  = ccd( $relative, $record['upload_file_name'] );
        $record['local_path']    = cca( $relative, $record['upload_file_name'] );
        $record['file_page_url'] = ccl('contest','media',$record['user_name'],$record['upload_id']) ;
    }

    /**
    * Event handler for getting renaming/id3 tagging macros
    *
    * @param array $record Record we're getting macros for (if null returns documentation)
    * @param array $patterns Substituion pattern to be used when renaming/tagging
    * @param array $mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros( &$record, &$patterns, &$mask )
    {
        if( empty($record) )
        {
            $patterns['%contest%']            = "Contest (Internal Name)";
            $patterns['%contest_fullname%']   = "Contest (Full Name)";
            $patterns['%url%']                = 'Download URL';
            $patterns['%song_page%']          = 'File page URL';
            $mask['contest']        = "Pattern to use for contest entries";
            $mask['contest-source'] = "Pattern to use for contest sources";
            return;
        }

        $isentry  = CCUploads::InTags( CCUD_CONTEST_ENTRY, $record );
        $issource = !$isentry && CCUploads::InTags( CCUD_CONTEST_ALL_SOURCES, $record );

        if( !($isentry || $issource)  )
            return;

        $configs =& CCConfigs::GetTable();
        $mask_configs = $configs->GetConfig('name-masks');

        if( $isentry )
        {
            $relative = $this->_get_user_upload_dir($record) . '/' . $record['upload_file_name'];
            if( array_key_exists('contest',$mask_configs) )
                $mask = $mask_configs['contest'];
        }
        elseif( $issource )
        {
            $relative = $this->_get_upload_dir($record) . '/' . $record['upload_file_name'];
            if( array_key_exists('contest-source',$mask_configs) )
                $mask = $mask_configs['contest-source'];
        }

        if( !empty($record['download_url']) )
        {
            $patterns['%url%']              = $record['download_url'];
            $patterns['%song_page%']        = $record['file_page_url'];
        }

        $patterns['%contest%']          = $record['contest_short_name'];
        $patterns['%contest_fullname%'] = $record['contest_friendly_name'];
    }


    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'contest',              array( 'CCContest', 'Contests'),        CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contest/media',        array( 'CCContest', 'Media'),           CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contest/current',      array( 'CCContest', 'CurrentContest'),  CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contest/create',       array( 'CCContest', 'CreateContest'),   CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'contest/newsource',    array( 'CCContest', 'SubmitSource'),    CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'contest/poll/results', array( 'CCContest', 'PollResults'),     CC_DONT_CARE_LOGGED_IN );
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
            $fields['contest-upload-root'] =
               array( 'label'       => 'Contest Upload Directory',
                       'form_tip'   => 'Contest files will be uploaded/downloaded based from here.(This must accessable from the Web.)',
                       'value'      => 'contests',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }
}


?>