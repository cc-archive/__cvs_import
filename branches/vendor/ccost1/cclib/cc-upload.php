<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
 * Base class for forms that uplaod media files.
 * 
 * @author victor
 */
class CCUploadMediaForm extends CCUploadForm 
{
    /**
     * Constructor.
     * 
     * Sets up basic editing fields for name, tags, description and the
     * file upload itself. Invokes the CC_UPLOAD_VALIDATOR 
     * to get a list of valid file types allowed for upload.
     *
     * @access public
     * @param integer $user_id This id represents the 'owner' of the media
     */
    function CCUploadMediaForm($user_id)
    {
        global $CC_UPLOAD_VALIDATOR;

        $this->CCUploadForm();
        $this->SetSubmitText('Upload');
        $this->SetHiddenField('upload_user', $user_id);

        $types = array();
        if( isset($CC_UPLOAD_VALIDATOR) )
            $CC_UPLOAD_VALIDATOR->GetValidFileTypes($types);

        if( empty($types) )
        {
            $form_tip = 'Specify file to upload';
        }
        else
        {
            $types = implode(', ',$types);
            $form_tip = "Valid file types: $types";
        }

        $fields = array( 
            'upload_name' =>
                        array( 'label'      => 'Name',
                               'formatter'  => 'textedit',
                               'form_tip'   => 'Display name for file',
                               'flags'      => CCFF_POPULATE ),

            'upload_file_name' =>
                       array(  'label'      => 'File',
                               'formatter'  => 'upload',
                               'form_tip'   => $form_tip,
                               'flags'      => CCFF_REQUIRED  ),
            'upload_tags' =>
                        array( 'label'      => 'Tags',
                               'formatter'  => 'tagsedit',
                               'form_tip'   => 'Comma separated list of terms',
                               'flags'      => CCFF_NONE ),

            'upload_description' =>
                        array( 'label'      => 'Description',
                               'formatter'  => 'textarea',
                               'flags'      => CCFF_POPULATE ),
                        );
        
        $this->AddFormFields( $fields );

        $this->_extra = array();
    }
}

/**
 * Extend this class for forms that upload new media to the system.
 *
 * @author victor
 */
class CCNewUploadForm extends CCUploadMediaForm
{
    /**
     * Constructor.
     *
     * Tweaks the bass class state to be in line with
     * new uploads, original or remixes.
     *
     * @access public
     * @param integer $userid The upload will be 'owned' by this user
     * @param integer $systags Set this to CCUD_REMIX in derived classes (defaults to CCUD_ORIGINAL)
     */
    function CCNewUploadForm($userid, $show_lic = true)
    {
        $this->CCUploadMediaForm($userid);

        $this->SetHiddenField('upload_date', date( 'Y-m-d H:i:0' ) );

        if( $show_lic )
        {
            $licenses =& CCLicenses::GetTable();
            $lics     = $licenses->GetEnabled();
            $count    = count($lics);
            if( $count == 1 )
            {
                $this->SetHiddenField('upload_license',$lics[0]['license_nic']);
            }
            elseif( $count > 1 )
            {
                $fields = array( 
                    'upload_license' =>
                                array( 'label'      => 'License',
                                       'formatter'  => 'metalmacro',
                                       'flags'      => CCFF_POPULATE,
                                       'macro'      => 'license.xml/license_choice',
                                       'license_choice' => $lics
                                )
                            );
                
                $this->AddFormFields( $fields );
            }
        }
        
    }

}

/**
 * This class is used to edit the values of media already in the system
 *
 * @author victor
 */
class CCEditFileForm extends CCUploadMediaForm 
{
    /**
     * Sets up the upload media form base class to act like a property form.
     *
     * Derived from UploadMediaForm in case the user wants to replace the
     * media file in the system for an existing record.
     *
     * @access public
     * @param integer $userid Owner of the media being edited
     * @param integer $fileid Database File ID being edited
     */
    function CCEditFileForm($userid,&$record)
    {
        $this->CCUploadMediaForm($userid);
        $this->SetHiddenField('upload_id' , $record['upload_id']);
        $this->SetFormValue('upload_tags', $record['upload_extra']['usertags']);
        $this->SetSubmitText('Save File Properties');

        $replace_fields = array(  
                               'label'      => 'Replace File',
                               'formatter'  => 'upload',
                               'form_tip'   => 'Replace the file currently uploaded',
                               'flags'      => CCFF_SKIPIFNULL
                               );

        $this->AddFormField('upload_file_name', $replace_fields);

    }

}

/**
*  Upload table wrapper
*
* Rules for when an upload is visible:
* 
*  These apply to uploads not part of a contest:
*  ---------------------------------------------
*        If the admin said auto-publish:
*            SHOW if : the user said ok to publish
*        If the admin wants to vet uploads:
*            SHOW if : the admin checked the publish bit
*            
*  These apply to a contest entry:
*  --------------------------------------------
*        If the admin said auto-publish:
*            SHOW
*        If the admin withholds entries until deadline:
*            SHOW if : after contest deadline
*
*  These two override all above states:
*  ----------------------------------
*  SHOW if : an admin is logged in
*  SHOW if : the registered user is the same as uploader
*
*      If we get into an override state:
*      ---------------------------------
*       Display a notice to user that file is 'unpublished'
*
*          If the current user is Admin:
*          -----------------------------
*            Display a command link to publish the work
*/
class CCUploads extends CCTable
{
    var $_tags;
    var $_filter;

    /**
     *   This is what we're aiming for:
     *
     *   SELECT *, DATE_FORMAT(upload_date, '$CC_SQL_DATE') as upload_date_format,
     *             DATE_FORMAT(user_registered, '$CC_SQL_DATE') as user_date_format  
     *       FROM cc_tbl_uploads
     *       LEFT OUTER JOIN cc_tbl_contests e ON upload_contest  = e.contest_id
     *       LEFT OUTER JOIN cc_tbl_user     u ON upload_user        = u.user_id
     &       LEFT OUTER JOIN cc_tbl_license  c ON upload_license     = c.license_id
    */
    function CCUploads()
    {
        global $CC_SQL_DATE;

        $this->CCTable('cc_tbl_uploads','upload_id');

        $this->AddJoin( new CCUsers(),    'upload_user');
        $this->AddJoin( new CCLicenses(), 'upload_license');
        $this->AddJoin( new CCContests(), 'upload_contest');
        
        $this->AddExtraColumn("DATE_FORMAT(upload_date, '$CC_SQL_DATE') as upload_date_format");
        $this->AddExtraColumn("DATE_FORMAT(user_registered, '$CC_SQL_DATE') as user_date_format");

        $this->_filter = '';

        // if the current user is admin, don't put 
        // any filters on the listings

        if( !CCUser::IsAdmin() )
        {
            if( CCUser::IsLoggedIn() )
            {
                $userid = CCUser::CurrentUser();

                // let the current user see all their
                // files no matter what

                $this->_filter =<<<END
                    (
                        upload_user = $userid
                    )
                    OR
END;
            }

            // todo: remove any notion of contest from here

            $entrybit    = CCUD_CONTEST_ENTRY;
            $sourcesbits = str_replace(',','|',CCUD_CONTEST_ALL_SOURCES);

            // if not part of a contest, then check the published bit
            // otherwise, show entries in an auto-publish contest or the source parts
            // failing that, show entries after the deadline has passed

            $this->_filter .=<<<END
                (
                    (
                       (upload_contest = 0) AND upload_published
                    )
                    OR
                    (
                        (
                            contest_auto_publish OR
                            (upload_tags REGEXP '(^| |,)($sourcesbits)(,|\$)' )
                        )
                        OR
                        (
                           (upload_tags REGEXP '(^| |,)($entrybit)(,|\$)' ) AND
                           ( NOW() > contest_deadline)
                        )
                    )
                )
END;

        } // endif for non-admin users filters

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
            $_table = new CCUploads();
        return( $_table );
    }

    function SetTagFilter($tags)
    {
        if( is_array($tags) )
            $tags = implode(', ',$tags);

        $this->_tags = $tags;
    }

    function & GetRecords($where)
    {
        $qr = $this->Query($where);
        $records = array();
        while( $row = mysql_fetch_assoc($qr) )
           $records[] =& $this->GetRecordFromRow($row);

        return( $records );
    }

    function & GetRecordFromID($fileid)
    {
        $row = $this->QueryKeyRow($fileid);
        return( $this->GetRecordFromRow($row) );
    }

    function & GetRecordsFromIDs($fileids)
    {
        $where = '';
        foreach( $fileids as $fileid )
            $where .= "( upload_id = '$fileid' ) OR ";
        $rows = $this->QueryRows( substr($where,0,-4) );
        return( $this->GetRecordsFromRows($rows) );
    }

    function & GetRecordFromRow($row)
    {
        $this->_format_file_size($row);

        if( is_string( $row['upload_extra'] ) )
            $row['upload_extra'] = unserialize($row['upload_extra']);

        if( array_key_exists('upldate_date',$row) )
            $row['rss_pubdate'] = substr($row['upload_date'],0,10);

        $tags =& CCTags::GetTable();
        $tags->ExpandOnRow( $row, 'upload_tags', ccl('tags'), 'upload_taglinks' );

        CCEvents::Invoke(CC_EVENT_UPLOAD_ROW, array( &$row ));

        return( $row );
    }

    function IsMediaType(&$row,$looking_for_media,$looking_for_ext='')
    {
        if( empty($row['upload_extra']['format_info']['media-type']) )
            return(false);
        $mt = $row['upload_extra']['format_info']['media-type'];
        $ok = ($mt == $looking_for_media);
        if( $ok && $looking_for_ext )
            $ok =  $row['upload_extra']['format_info']['default-ext'] == $looking_for_ext;
        return($ok);
    }

    function GetFormatInfo(&$row,$field='')
    {
        if( empty($row['upload_extra']['format_info']) )
            return(null);
        $F = $row['upload_extra']['format_info'];
        if( $field && empty($F[$field]) )
            return( null );
        if( $field )
            return( $F[$field] );
        return( $F );
    }

    function SetExtraField( $id_or_row, $fieldname, $value)
    {
        $this->_get_extra_helper($id_or_row,$extra,$id);
        $extra[$fieldname] = $value;
        $args['upload_extra'] = serialize($extra);
        $args['upload_id'] = $id;
        $this->Update($args);
    }

    function GetExtraField( &$id_or_row, $fieldname )
    {
        $this->_get_extra_helper($id_or_row,$extra,$id);
        if( !empty($extra[$fieldname]) )
            return( $extra[$fieldname] );

        return( null );
    }

    function _get_extra_helper( &$id_or_row, &$extra, &$id )
    {
        if( is_array($id_or_row) )
        {
            $extra = $id_or_row['upload_extra'];
            $id = $id_or_row['upload_id'];
        }
        else
        {
            $id = $id_or_row['upload_id'];
            $row = $this->QueryKeyRow($id);
            $extra = unserialize($row['upload_extra']);
        }

    }

    function InTags($tags,&$record)
    {
        return( CCTag::InTag($tags,$record['upload_tags']));
    }

    function SplitTags(&$record)
    {
        return( CCTag::TagSplit($record['upload_tags']) );
    }

    function _format_file_size(&$row)
    {
        $fs = $row['upload_filesize'];
        if( $fs )
        {
            if( $fs > CC_1MG )
                $fs = number_format($fs/CC_1MG,2) . 'MG';
            else
                $fs = number_format($fs/1024) . 'Kb';
            $row['upload_filesize'] = " ($fs)";
        }
        else
        {
            $row['upload_filesize'] = '';
        }
    }

    // overwrite parent's version to add descriptor
    function _get_select($where,$columns='*')
    {
        $where = $this->_where_to_string($where);

        if( !empty($this->_tags) )
        {
            $tagors = str_replace(',','|',$this->_tags);
            $filter = " upload_tags REGEXP '(^| |,)($tagors)(,|\$)' ";
            if( empty($where) )
                $where = $filter;
            else
                $where = "($where) AND ($filter)";
        }

        if( !empty($this->_filter) )
        {
            if( empty($where) )
                $where = $this->_filter;
            else
                $where = "($where) AND \n ({$this->_filter})";
        }

        return( parent::_get_select($where,$columns) );
    }
}


class CCConfirmDeleteForm extends CCForm
{
    function CCConfirmDeleteForm($pretty_name)
    {
        $this->CCForm();
        $this->SetHelpText("This action can not be reversed...");
        $this->SetSubmitText("Delete \"$pretty_name\" ?");
    }
}
// -----------------------------
//  Main Upload API
// -----------------------------
class CCUpload
{
    function ListMultipleFiles($sql_where = '', $ccud = CCUD_MEDIA_BLOG_UPLOAD)
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetSort( 'upload_date', 'DESC' );
        $uploads->SetTagFilter($ccud);
        $records =& $uploads->GetRecords($sql_where);
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $menu = CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,array(&$records[$i]));
            $records[$i]['local_menu'] = $menu;
            CCEvents::Invoke(CC_EVENT_UPLOAD_LISTING, array(&$records[$i]));
        }

        CCPage::PageArg( 'file_record', $records, 'upload.xml/file_listing' );
    }

    function ListFile( $username, $fileid, $ccud = CCUD_MEDIA_BLOG_UPLOAD )
    {
        $uploads =& CCUploads::GetTable();
        $pretty_name = $uploads->QueryItemFromKey('upload_name',$fileid);
        CCPage::SetTitle($pretty_name);
        $where['upload_id'] = $fileid;
        CCUpload::ListMultipleFiles( $where, $ccud );
    }
  
    function Edit($username,$fileid)
    {
        CCUpload::CheckFileAccess($username,$fileid);

        $this->_set_edit_title($fileid);

        $userid = CCUser::IDFromName($username);
        $uploads =& CCUploads::GetTable();
        $record = $uploads->GetRecordFromID($fileid);
        $form = new CCEditFileForm($userid,$record);
        $show = true;
        if( empty($_POST['editfile']) )
        {
            $form->PopulateValues($record);
        }
        else
        {
            if( $form->ValidateFields() )
            {
                $sources      =& CCRemixSources::GetTable();
                $remixsources = $sources->GetSourcesForID($fileid);
                $this->PostProcessUpload($form,
                                         $record['upload_extra']['ccud'],
                                         dirname($record['local_path']),
                                         $remixsources,
                                         $record );
                
                CCPage::Prompt("Changes were saved");
                //$this->ListFile( $username, $fileid );
                $show = false;
            }
        }

        if( $show )
            CCPage::AddForm( $form->GenerateForm() );
    }

    function _set_edit_title($fileid)
    {
        $uploads =& CCUploads::GetTable();
        $pretty_name = $uploads->QueryItemFromKey('upload_name',$fileid);
        CCPage::SetTitle("Edit Properties for $pretty_name");
    }

    function Delete($fileid)
    {
        if( !CCUser::IsAdmin() )
            CCUser::CheckCredentials(CCUser::CurrentUser(),$fileid);
        $uploads =& CCUploads::GetTable();
        if( empty($_POST['confirmdelete']) )
        {
            $pretty_name = $uploads->QueryItemFromKey('upload_name',$fileid);
            $form = new CCConfirmDeleteForm($pretty_name);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $where['upload_id'] = $fileid;
            $records = $uploads->GetRecords($where);    
            if( empty($records) )
            {
                CCPage::Prompt("That file doesn't seem to exist (?)");
            }
            else
            {
                $arg = array( &$records[0] );
                CCEvents::Invoke(CC_EVENT_DELETE_UPLOAD, $arg);
                //CCDebug::PrintVar($arg);
                $record = $arg[0];
                if( file_exists($record['local_path']) )
                    @unlink($record['local_path']);
                $uploads->DeleteWhere($where);
                CCPage::Prompt("Upload has been deleted");
            }
        }
    }

    function CheckFileAccess($usernameorid,$fileid)
    {
        CCUser::CheckCredentials($usernameorid);
        $uploads =& CCUploads::GetTable();
        if( !intval($usernameorid) )
            $usernameorid = CCUser::IDFromName($usernameorid);
        $fileowner = $uploads->QueryItemFromKey('upload_user',$fileid);
        $s = "arg: $usernameorid / owner: $fileowner";
        if(  $fileowner != $usernameorid )
        {
            CCUtil::AccessError( __FILE__, __LINE__ );
        }
    }

    function PostProcessUpload( &$form, $ccud_tags, $relative_dir, $parents = null, $oldrecord = null )
    {
        $form->GetFormValues($values);

        $ret = CCUpload::PostProcessUploadNoUI($values,$ccud_tags,$values['upload_tags'],$relative_dir,$oldrecord,$parents);

        if( is_string($ret) )
        {
            $form->SetFieldError('upload_file_name',$ret);
            return(0);
        }

        return($ret);
    }

    function PostProcessUploadNoUI( $dbargs, 
                                    $ccud_tags,
                                    $user_tags,
                                    $relative_dir,
                                    $oldrecord, 
                                    $parents)
    {
        $uploads =& CCUploads::GetTable();

        // todo: a problem here: the verifier requires an mp3 extension for
        //        solid verification/in mixter we moved it to some known location and renamed it 
        //        before whacking on it. maybe based on mime type we can take a stab at that.
        //
        //
        if( !empty($dbargs['upload_file_name']) )
        {
            $isupload = true;
            $current_path = $dbargs['upload_file_name']['tmp_name'];

            //
            // Verfiy file format.
            //
            global $CC_UPLOAD_VALIDATOR;
            if( isset($CC_UPLOAD_VALIDATOR) )
            {
                $format_info =  new CCFileFormatInfo($current_path);
                $CC_UPLOAD_VALIDATOR->FileValidate( $format_info );
                $errors = $format_info->GetErrors();
                if( !empty($errors) )
                {
                    return( "There was error in the file format<br />" . implode("<br />", $errors ) );
                }
            }

            $dbargs['upload_file_name'] = $dbargs['upload_file_name']['name'];
        }
        else
        {
            // this happens when user edits the properties of a file
            // but doesn't post a replacement file, so we not hear to 
            // actually move files around but (possibly) rename and 
            // retag

            $isupload = false;
            $current_path = $oldrecord['local_path'];
            $dbargs['upload_file_name'] = $oldrecord['upload_file_name'];
        }

        if( !empty($oldrecord) )
        {
            if( empty($dbargs['upload_license']) )
                $dbargs['upload_license'] = $oldrecord['upload_license'];
        }

        //
        // Ensure friendly (display) name is not blank
        //
        if( empty( $dbargs['upload_name'] ) )
            $dbargs['upload_name'] = CCUtil::BaseFile($dbargs['upload_file_name']);

        if( !empty($oldrecord) )
            $dbargs['upload_extra'] = $oldrecord['upload_extra'];

        if( !empty($format_info) )
            $dbargs['upload_extra']['format_info'] = $format_info->GetData();

        $dbargs['upload_extra']['ccud']        = CCUpload::_concat_tags($ccud_tags);
        $dbargs['upload_extra']['usertags']    = CCUpload::_concat_tags($user_tags);
        

        // Make a copy so renaming and tagging can wreak havoc
        // (we want to keep dbargs clean so we can use it for update/insert
        $copyargs = $dbargs;

        // 
        // Get remix children (if any) 
        // This has to happen before renaming and ID3 tagging
        //
        if( $parents )
        {
            $copyargs['remix_sources'] =& $parents;
        }

        //
        // Fake all joins it looks to rename/tagid3 etc. like a real record
        //
        $uploads->FakeJoin($copyargs);

        //
        // Get format tags
        //
        // This has to happen before renaming so modules know which mask to use
        //
        $tagarr = array();
        CCEvents::Invoke( CC_EVENT_GET_SYSTAGS, array( &$copyargs, &$tagarr ) );
        $copyargs['upload_tags'] = 
        $dbargs['upload_tags']   = CCUpload::_concat_tags( $dbargs['upload_extra']['ccud'],
                                               $tagarr,
                                               $dbargs['upload_extra']['usertags']
                                                );

        //
        // Rename/move file according to users macros
        //
        global $CC_RENAMER;
        if( isset($CC_RENAMER) )
            $CC_RENAMER->Rename($copyargs);

        if( $copyargs['upload_file_name'] )
        {
            $copyargs['upload_file_name'] = 
            $dbargs['upload_file_name']   = CCUtil::LegalFileName($copyargs ['upload_file_name']);
        }

        CCUtil::MakeSubdirs( $relative_dir ); // you have to make the dir for realpath() to work

        $current_path  = str_replace('\\', '/', $current_path);
        $new_path      = str_replace( '\\', '/', realpath($relative_dir) . '/' . $dbargs['upload_file_name'] );

        if( $new_path != $current_path )
        {
            if( $isupload && !empty($oldrecord) )
            {
                if( $oldrecord['upload_file_name'] != $dbargs['upload_file_name'] )
                    unlink( $oldrecord['local_path'] );
            }

            if( file_exists($new_path) )
                unlink($new_path);

            $is_up =  is_uploaded_file($current_path);

            if( $is_up )
                $ok = move_uploaded_file($current_path,$new_path);
            else
                $ok = rename($current_path,$new_path);

            if( !$ok )
            {
                $msg = "Rename to $new_path failed ($is_up)";
                return( $msg );
            }
            
            if( !file_exists($new_path) )
            {
                $msg = "Move to $new_path failed ($is_up)";
                return( $msg );
            }

        }

        // Generate a unique ID for this record
        // (we need it to build paths)
        //
        if( empty($copyargs['upload_id']) )
        {
            $copyargs['upload_id'] = $dbargs['upload_id'] = $uploads->NextID();
        }

        // GetRecords wants filesize
        $copyargs['upload_filesize'] = filesize($new_path);
        
        // This fills in local_path, download_url and other things
        // the ID3 Tagger will want to see
        //
        $copyargs = $uploads->GetRecordFromRow($copyargs);

        //
        // Stamp ID3 tags 
        //
        global $CC_ID3_TAGGER;
        if( isset($CC_ID3_TAGGER) )
            $CC_ID3_TAGGER->TagFile( $copyargs );

        //
        // Do sh1, magnet link and other post upload stuff
        //
        CCEvents::Invoke( CC_EVENT_FINALIZE_UPLOAD, array( &$copyargs ) );

        // 
        // Update the 'uploads' table
        // (The filesize was formatted and may have changed when tagging and other hyjinks)
        //
        $dbargs['upload_filesize'] = filesize($new_path);
        $dbargs['upload_extra']    = serialize($copyargs['upload_extra']);
        if( empty($oldrecord) )
            $uploads->Insert($dbargs);
        else
            $uploads->Update($dbargs);

        // update Tags table
        //
        $tags =& CCTags::GetTable();
        if( empty($oldrecord) )
            $tags->UpdateOrAdd($dbargs['upload_tags']);
        else
            $tags->Replace($oldrecord['upload_tags'],$dbargs['upload_tags']);

        return( intval($dbargs['upload_id']) );
    }

    function _concat_tags()
    {
        $ts = func_get_args();
        $result = '';
        foreach($ts as $t)
        {
            if( is_array($t) )
                $t = implode(',',$t);
            $t = trim($t);
            if( !$t )
                continue;
            if( empty($result) )
                $result = $t;
            else
                $result .= ',' . $t;
        }

        return( $result);
    }
}


?>