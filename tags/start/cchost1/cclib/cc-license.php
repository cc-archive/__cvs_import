<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_FINALIZE_UPLOAD,     array( 'CCLicense',  'OnFinalizeUpload'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,          array( 'CCLicense' , 'OnGetMacros'));
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,         array( 'CCLicense',  'OnGetSysTags'));
CCEvents::AddHandler(CC_EVENT_MAIN_MENU,           array( 'CCLicense',  'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,            array( 'CCLicense',  'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,          array( 'CCLicense',  'OnUploadRow'));

/**
 * Form class for configuring licenses.
 *
 * @access public
 */
class CCAdminLicenseForm extends CCForm
{
    /**
     * Constructor.
     *
     * Sets up fields as read from the license database.
     *
     * @access public
     * @param  boolean $populate True if fields are to be populated with values in the database
     */
    function CCAdminLicenseForm($populate)
    {
        $this->CCForm();
        $licenses =& CCLicenses::GetTable();
        $rows = $licenses->QueryRows('');
        $fields = array();
        foreach($rows as $row)
        {
            $value = $populate && $row['license_enabled'] ? 'checked' : '';

            $fields[ $row['license_id'] ] = 
                       array( 'label'       => 'Enabled:',
                               'value'      => $value,
                               'license'    => $row,
                               'formatter'  => 'metalmacro',
                               'macro'      => 'license.xml/license_enable',
                               'flags'      => CCFF_NONE );
        }
        $this->AddFormFields($fields);
    }

}

/**
* Wrapper class for license information table
*
* This is just syntantic sugar on to of CCTable
*/
class CCLicenses extends CCTable
{
    /**
    * Constructor
    *
    */
    function CCLicenses()
    {
        $this->CCTable('cc_tbl_licenses','license_id');
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
            $_table = new CCLicenses();
        return( $_table );
    }

    /**
    * Get rows with enabled flag turned on (this is bogus and will change)
    *
    * @returns array $rows Returns CCLicense table object rows
    */
    function GetEnabled()
    {
        $rows = $this->QueryRows( 'license_enabled > 0' );
        if( !empty($rows) )
            $rows[0]['license_checked'] = true;
        return( $rows );
    }
}

/**
* License event handlers and API
*
*/
class CCLicense
{
    /**
    * Event handler for getting renaming/id3 tagging macros
    *
    * @param array $record Record we're getting macros for (if null returns documentation)
    * @param array $patterns Substituion pattern to be used when renaming/tagging
    * @param array $mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros(&$record,&$patterns,&$masks)
    {
        if( empty($record) )
        {
            $patterns['%license_url%'] = "License URL";
            $patterns['%license%']     = "License name";
        }
        else
        {
            $patterns['%license_url%'] = $record['license_url'];
            $patterns['%license%'] = $record['license_name'];

        }
    }

    /**
    * Event handler for CC_EVENT_GET_SYSTAGS 
    *
    * @param array $record Record we're getting tags for 
    * @param array $tags Place to put the appropriate tags.
    */
    function OnGetSysTags(&$record,&$tags)
    {
        if( empty($record['license_tag']) )
        {
            if( !empty($record['upload_license']) )
            {
                $licenses =& CCLicenses::GetTable();
                $where['license_name'] = $record['upload_license'];
                $tags[] = $licenses->QueryItem('license_tag',$where);
            }

        }
        else
        {
            $tags[] = $record['license_tag'] ;
        }
    }

    function OnFinalizeUpload(&$record)
    {
        // calculate sha1
        $sha1 = sha1_file($record['local_path']) ;
        $record['upload_extra']['sha1'] = $this->_hex_to_base32($sha1);

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
        if( empty($record['upload_license']) || empty($record['works_page']) )
            return;

        $types = array( 'permits', 'prohibits', 'required' );
        foreach( $types as $type )
        {
            if( empty($record['license_' . $type]) )
                unset($record['license_' . $type]);
            else
                $record['license_' . $type] = split(',',$record['license_' . $type]);
        }

        $record['year'] = date('Y', strtotime($record['upload_date']) );
        $record['start_comm'] = "<!--";
        $record['end_comm'] = "-->";

        $record['file_macros'][] = 'license.xml/license_rdf';

    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'licadmin'=> array('menu_text'  => 'License',
                                 'menu_group' => 'configure',
                                 'access'     => CC_ADMIN_ONLY,
                                 'weight'     => 16,
                                 'action'     => ccl('admin','license') )
                        );
        
        CCMenu::AddItems($items);

    }

    function Admin()
    {
        CCPage::SetTitle("Configure Licenses");
        if( empty($_POST['adminlicense']) )
        {
            $form = new CCAdminLicenseForm(true);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form = new CCAdminLicenseForm(false);
            if( $form->ValidateFields() )
            {
                $form->GetFormValues($values);
                $licenses =& CCLicenses::GetTable();
                foreach( $values as $id => $value )
                {
                    $f = array( 'license_id' => $id, 
                                'license_enabled' => empty($value) ? false : true );
                    $licenses->Update($f);
                }
                CCPage::Prompt("Licenses Updated");
            }
        }
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/license',  array('CCLicense', 'Admin'), CC_ADMIN_ONLY  );
    }

    // used for hashing files
    function _hex_to_base32($hex) 
    {
      $b32_alpha_to_rfc3548_chars = array(
        '0' => 'A',
        '1' => 'B',
        '2' => 'C',
        '3' => 'D',
        '4' => 'E',
        '5' => 'F',
        '6' => 'G',
        '7' => 'H',
        '8' => 'I',
        '9' => 'J',
        'a' => 'K',
        'b' => 'L',
        'c' => 'M',
        'd' => 'N',
        'e' => 'O',
        'f' => 'P',
        'g' => 'Q',
        'h' => 'R',
        'i' => 'S',
        'j' => 'T',
        'k' => 'U',
        'l' => 'V',
        'm' => 'W',
        'n' => 'X',
        'o' => 'Y',
        'p' => 'Z',
        'q' => '2',
        'r' => '3',
        's' => '4',
        't' => '5',
        'u' => '6',
        'v' => '7'
      );
      $b32_alpha = '';
      for ($pos = 0; $pos < strlen($hex); $pos += 10) {
        $hs = substr($hex,$pos,10);
        $b32_alpha_part = base_convert($hs,16,32);
        $expected_b32_len = strlen($hs) * 0.8;
        $actual_b32_len = strlen($b32_alpha_part);
        $b32_padding_needed = $expected_b32_len - $actual_b32_len;
        for ($i = $b32_padding_needed; $i > 0; $i--) {
          $b32_alpha_part = '0' . $b32_alpha_part;
        }
        $b32_alpha .= $b32_alpha_part;
      }
      $b32_rfc3548 = '';
      for ($i = 0; $i < strlen($b32_alpha); $i++) {
        $b32_rfc3548 .= $b32_alpha_to_rfc3548_chars[$b32_alpha[$i]];
      }
      return $b32_rfc3548;
    }
}

?>