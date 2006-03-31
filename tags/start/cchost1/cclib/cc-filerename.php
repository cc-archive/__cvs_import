<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,       array( 'CCFileRename', 'OnBuildMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,        array( 'CCFileRename', 'OnMapUrls') );

/**
* Admin form for upload renaming rules
*/
class CCAdminRename  extends CCEditConfigForm
{
    /**
    * Constructor
    *
    * Every module in the system has the opportunity to participate in the renaming
    * rules by responding to CC_EVENT_GET_MACROS event (triggered by this method).
    * In this case the $record field will be blank and therefore the documentation
    * for each mask and renaming tagging macro is expected back.
    *
    */
    function CCAdminRename()
    {
        $this->CCEditConfigForm('name-masks');
        
        $this->SetHelpText("Replacement Macro Table:");

        $patterns['%title%'] = "Title";
        $patterns['%site%']  = "Site name";
        $dummy = array();
        $masks = array();
        CCEvents::Invoke( CC_EVENT_GET_MACROS, array( &$dummy, &$patterns, &$masks ) );
        ksort($patterns);
        $this->CallFormMacro('macro_patterns','misc.xml/macro_patterns',$patterns);

        $fields = array();

        foreach( $masks as $mask => $label )
        {
            $fields[$mask] = 
                        array( 'label'      => $label,
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE );
        }

        $fields['upload-replace-sp'] =
                        array( 'label'      => "Replace space with '_'",
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE );

        $this->AddFormFields($fields);
    }
}

$CC_RENAMER = new CCFileRename();

class CCFileRename
{
    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'adminrename' => array( 'menu_text'  => 'Upload Renaming',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 30,
                             'action' =>  ccl('admin','renaming')
                             ),
            );

        CCMenu::AddItems($items);
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/renaming',  array( 'CCFileRename', 'AdminRenaming'), CC_ADMIN_ONLY );
    }

    /**
    * Handler for admin/renaming - put up form
    *
    * @see CCAdminRename::CCAdminRename
    */
    function AdminRenaming()
    {
        CCPage::SetTitle("Edit Upload Renaming Rules");

        $form = new CCAdminRename();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Method that does the upload renaming according to rules set by user
    *
    * Every module in the system has the opportunity to participate in the renaming
    * rules by responding to CC_EVENT_GET_MACROS event (triggered by this method).
    * If the handler thinks it 'owns' the upload it should return the 'mask' to 
    * use. All respondents are responsible for retuning the macro in the mask as
    * well as the value associated with the upload record.
    *
    * This method is called by checking for the global '$CC_RENAMER' and then
    * calling $CC_RENAMER->Rename($record).
    *
    * If everything works out OK, this method will populate the 'upload_file_name'
    * field;
    *
    * <code>
        
        // get $record from database or user filled out form...

        $oldname = $record['upload_file_name'];

        if( isset($CC_RENAMER) )
        {
            if( $CC_RENAMER->Rename($record) )
            {
                rename( $oldname, $record['upload_file_name'] );
                $uploads->Update($record);
            }
        }

    * </code>
    *
    * @see CCUpload::PostProcessUploadNoUI
    * @param array $record Database record of upload
    * @returns boolean $renamed true if file was replaced
    */
    function Rename(&$record)
    {
        $configs             =& CCConfigs::GetTable();
        $template_tags       = $configs->GetConfig('ttag');
        $settings            = $configs->GetConfig('name-masks');

        $patterns['%title%'] = $record['upload_name'];
        $patterns['%site%']  = $template_tags['site-title'];
        $mask                = '';
        $args                = array( &$record, &$patterns, &$mask );

        CCEvents::Invoke( CC_EVENT_GET_MACROS, $args );
        
        if( !empty($mask) )
        {
            $newname = CCMacro::TranslateMask($patterns,$mask,$settings['upload-replace-sp']);
            if( !empty($newname) )
            {
                $record['upload_file_name'] = $newname;
                return( true );
            }
        }
        
        return( false );
    }


}




?>