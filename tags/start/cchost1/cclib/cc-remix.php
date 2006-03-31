<?php

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_LISTING, array( 'CCRemix', 'OnUploadListing'));
CCEvents::AddHandler(CC_EVENT_DELETE_UPLOAD,  array( 'CCRemix', 'OnUploadDelete'));

/**
 * Base class for uploading remixes form
 *
 * Note: derived classes must call SetHandler()
 * @access public
 */
class CCPostRemixForm extends CCNewUploadForm
{
    /**
     * Constructor
     *
     * Sets up form as a remix form. Initializes 'remix search' box.
     *
     * @access public
     * @param integer $userid The remix will be 'owned' by owned by this user
     */
    function CCPostRemixForm($userid)
    {
        $this->CCNewUploadForm($userid,false);
        $this->CallFormMacro('remix_search','misc.xml/remix_search');
    }

    /**
     * Overrides the base class and only displays fields if search results is not empty.
     *
     *
     * @access public
     */
    function GenerateForm()
    {
        if( $this->TemplateVarExists('remix_sources') )
        {
            parent::GenerateForm(false);
        }
        else
        {
            $this->SetSubmitText(null);
            parent::GenerateForm(true); // hiddenonly = true
        }

        return( $this );
    }

}


/**
 * Base class for remix fork database table
 *
 * @access private
 */
class CCRemixTree extends CCTable
{
    var $_bind_to_query;
    var $_uploads;

    function CCRemixTree($bind_to_upload,$bind_to_query)
    {
        $this->CCTable('cc_tbl_tree',$bind_to_upload);
        $this->_bind_to_query = $bind_to_query;
    }

    function & _get_relatives($fileid)
    {
        if( !isset($this->_uploads) )
            $this->_uploads = new CCUploads();

        $joinid = $this->_uploads->AddJoin($this,'upload_id');
        $rows = $this->_uploads->QueryRows( $joinid . '.' . $this->_bind_to_query . " = '$fileid'");
        $this->_uploads->RemoveJoin($joinid);
        $records =& $this->_uploads->GetRecordsFromRows($rows);
        return( $records );
    }

}

/**
 * Virtual table class to represent the remix sources of a remix
 *
 * @access public
 */
class CCRemixSources extends CCRemixTree
{
    function CCRemixSources()
    {
        $this->CCRemixTree('tree_parent','tree_child');
    }

    function & GetSourcesForID($fileid)
    {
        return( $this->_get_relatives($fileid) );
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
            $_table = new CCRemixSources();
        return( $_table );
    }
}

/**
 * Virtual table class to represent the remixes of a given source
 *
 * @access public
 */
class CCRemixes extends CCRemixTree
{
    function CCRemixes()
    {
        $this->CCRemixTree('tree_child','tree_parent');
    }

    function & GetRemixesForID($fileid)
    {
        return( $this->_get_relatives($fileid) );
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
            $_table = new CCRemixes();
        return( $_table );
    }
}

/**
 * Remix API
 *
 * @access public
 */
class CCRemix
{
    function OnPostRemixForm($form, $relative_dir, $ccud = CCUD_REMIX)
    {
        $uploads =& CCUploads::GetTable();

        if( !empty($_POST['remix_sources']) )
        {
            $remix_check_boxes = array_keys($_POST['remix_sources']);
            $remix_sources =& $uploads->GetRecordsFromIDs($remix_check_boxes);
            if( !empty($remix_sources) )
            {
                $form->SetTemplateVar( 'remix_sources', $remix_sources );
                CCRemix::StrictestLicense($form);
            }
        }

        if( !empty($_POST['form_submit']) )
        {
            if( $form->TemplateVarExists('remix_sources') && $form->ValidateFields() )
            {
                // CCDebug::LogVar("form",$form);
                $remixid = CCUpload::PostProcessUpload($form,
                                                       $ccud,
                                                       $relative_dir,
                                                       $remix_sources);

                $sourceids = array_keys($_POST['remix_sources']);

                if( $remixid )
                {
                    $all_fields = array();
                    foreach( $sourceids as $sourceid )
                    {
                        $fields = array();
                        $fields['tree_parent'] = CCUtil::StripText($sourceid);
                        $fields['tree_child']  = $remixid;
                        $all_fields[] = $fields;
                    }

                    $remixes =& CCRemixes::GetTable();
                    $remixes->InsertBatch( array('tree_parent','tree_child'), $all_fields );


                    CCPage::Prompt("Upload succeeded");
                    $where['upload_id'] = $remixid;
                    CCUpload::ListMultipleFiles($where,$ccud);
                    return(true);
                }
            }
        }
        else if( !empty($_POST['search']) )
        {
            $query = CCUtil::StripText($_POST['remix_search_query']);
            if( !empty($query) )
            {
                CCSearch::DoSearch( $query, 'any', CC_SEARCH_UPLOADS, $results );
                if( !empty($results[CC_SEARCH_UPLOADS]) )
                    $form->SetTemplateVar( 'remix_search_result', $results[CC_SEARCH_UPLOADS] );
            }
        }

        CCPage::AddForm( $form->GenerateForm() );

        return( false );
    }

    function StrictestLicense( &$form )
    {
        $rows = $form->GetTemplateVar( 'remix_sources' );

        $license = '';
        $strict = 0;
        foreach( $rows as $row )
        {
            if( !$license || ($strict < $row['license_strict'] ) )
            {
                $strict  = $row['license_strict'];
                $license = $row['license_id'];
            }
        }

        $form->CallFormMacro( 'remix_license','misc.xml/remix_license' );
        $form->SetHiddenField( 'upload_license', $license, CCFF_HIDDEN | CCFF_STATIC );
        $lics =& CCLicenses::GetTable();
        $licenserow = $lics->QueryKeyRow($license);
        $form->AddTemplateVars( $licenserow  );
    }

    function OnUploadDelete( &$row )
    {
        $id = $row['upload_id'];
        $where = "(tree_parent = $id) OR (tree_child = $id)";
        $tree = new CCRemixTree('tree_parent','tree_child');
        $tree->DeleteWhere($where);
    }

    function OnUploadListing( &$row )
    {
        $remixes =& CCRemixes::GetTable();
        $children = $remixes->GetRemixesForID($row['upload_id']);
        if( !empty($children) ) 
        {
            $row['remix_children'] = $children;
            $row['file_macros'][] = 'upload.xml/remix_children';
        }

        $remix_sources =& CCRemixSources::GetTable();
        $parents = $remix_sources->GetSourcesForID($row['upload_id']);
        if( !empty($parents) )
        {
            $row['remix_parents'] = $parents;
            $row['file_macros'][] = 'upload.xml/remix_parents';
        }
    }

}


?>