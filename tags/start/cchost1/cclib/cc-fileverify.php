<?
// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,           array( 'CCFileVerify', 'OnBuildMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,            array( 'CCFileVerify', 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_GET_SYSTAGS,         array( 'CCFileVerify', 'OnGetSysTags'));
CCEvents::AddHandler(CC_EVENT_GET_MACROS,          array( 'CCFileVerify', 'OnGetMacros'));

$CC_UPLOAD_VALIDATOR = new CCFileVerify();

/**
* Form for configuration the file format verification module
*
*/
class CCAdminFileVerifyForm extends CCEditConfigForm
{
    /**
    * Constructor
    *
    */
    function CCAdminFileVerifyForm()
    {
        $this->CCEditConfigForm('format-allow');

        $fields = array();
        
        $formats =& CCGetID3::GetFormats();

        foreach( $formats as $name => $format )
        {
            $fields[$name] =
                       array(  'label'       => "Allow " . $format['description'],
                               'form_tip'    => '(' . $format['name'] . ')',
                               'formatter'   => 'checkbox',
                               'flags'       => CCFF_POPULATE );
        }

        $this->AddFormFields( $fields );
    }
}

/**
* Default file verification API (wrapper for GetID3 library)
*
*/
class CCFileVerify
{
    /**
    * Returns a list of admin accepted file formats.
    * 
    * This method is called by checking for the global '$CC_UPLOAD_VALIDATOR' and then
    * calling $CC_UPLOAD_VALIDATOR->GetValidFileTypes($types).
    * 
    * <code>

        $type = array();
        if( isset($CC_UPLOAD_VALIDATOR) )
        {
            $CC_UPLOAD_VALIDATOR->GetValidFileTypes($types);
        }

    * </code>
    * 
    * @param array $types Outbound parameter to put the valid format types
    * @returns bool $havetypes true if there is at least one type
    */
    function GetValidFileTypes(&$types)
    {
        $configs =& CCConfigs::GetTable();
        $allowed = $configs->GetConfig('format-allow');
        $formats =& CCGetID3::GetFormats();

        foreach($allowed as $allow => $value )
        {
            if( $value )
                $types[] = $formats[$allow]['name'];
        }

        return( count($types) > 0 );
    }


    /**
    * Validates a file to be of a certain type.
    *
    * This method is called by checking for the global '$CC_UPLOAD_VALIDATOR' and then
    * calling $CC_UPLOAD_VALIDATOR->FileValidate($formatinfo).
    * 
    * <code>

        $format_info = new CCFileFormatInfo('/some/path/to/file');

        if( isset($CC_UPLOAD_VALIDATOR) )
        {
            if( $CC_UPLOAD_VALIDATOR->FileValidate($format_info) )
            {
                // validated ok
            }
            else
           {
               // handle problems
              $errors = $format_info->GetErrors();
           }
        }

    * </code>
    *
    * @see CCFileFormatInfo::CCFileFormatInfo
    * @see CCUpload::PostProcessUploadNoUI
    * @param object $formatinfo Database record of upload
    * @returns boolean $renamed true if file was replaced
    */
    function FileValidate(&$formatinfo)
    {
        $retval = false;
        $path = $formatinfo->GetFilePath();

        CCDebug::QuietErrors();
        $id3 =& CCGetID3::InitID3Obj();
        $tags = $id3->analyze($path);
        CCDebug::RestoreErrors();

        //CCDebug::PrintVar($tags);

        if( isset($tags['warning']) )
        {
            $formatinfo->SetWarnings($tags['warning']);
        }

        if( isset($tags['error']) )
        {
           $formatinfo->SetErrors($tags['error']);
        }
        else
        {
            $name = $this->_parse_format_name($tags);

            if( $name )
            {
                $formats =& CCGetID3::GetFormats();
                if( array_key_exists($name,$formats) )
                {
                    $configs =& CCConfigs::GetTable();
                    $allowed = $configs->GetConfig('format-allow');
                    if( empty($allowed[$name]) )
                    {
                        $formatinfo->SetErrors("File type is not allowed");
                    }
                    else
                    {
                        $this->_ID3_to_format_info($tags,$format_data,$name);
                        $formatinfo->SetData($format_data);
                        $retval = true;
                    }
                }
            }
        }
        


        return( $retval );
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
            $patterns['%ext%']      = "File extention (based on file format)";
            $patterns['%filename%'] = "%itle% + %ext% ";
            return;
        }

        if( empty($record['upload_extra']['format_info']) )
            return;

        $F = $record['upload_extra']['format_info'];

        if( isset($F['default-ext']) )
        {
            $patterns['%ext%']      = $F['default-ext'];
            $patterns['%filename%'] = $patterns['%title%'] . '.' .  $F['default-ext'];
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
        if( empty($record['upload_extra']['format_info']) )
            return;

        $F = $record['upload_extra']['format_info'];

        if( !is_array($F) || !array_key_exists('format-name',$F) )
            return;

        $names = array( 'media-type', 'default-ext', 'sr', 'ch', 'br' );

        foreach( $names as $name )
        {
            if( isset($F[$name]) )
                $tags[] = $F[$name];
        }
    }


    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
        'configformats'   => array( 'menu_text'  => 'File Formats',
                         'menu_group' => 'configure',
                         'access' => CC_ADMIN_ONLY,
                         'weight' => 10,
                         'action' =>  ccl('admin','formats')
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
        CCEvents::MapUrl( 'admin/formats',  array('CCFileVerify', 'ConfigureFormats'), CC_ADMIN_ONLY );
    }

    function ConfigureFormats()
    {
        CCPage::SetTitle("Edit Allowable File Formats");

        $form = new CCAdminFileVerifyForm($this);
        CCPage::AddForm( $form->GenerateForm() );
    }

    function _ID3_to_format_info(&$id3obj,&$F, $name)
    {
        $formats =& CCGetID3::GetFormats();
        list( $mediatype ) = split('-',$name);
        $F['media-type']  = $mediatype;
        $F['format-name'] = $name;
        $F['default-ext'] = $formats[$name]['name'];

        if( !empty($id3obj['audio']['sample_rate']) )
        {
            $v = $id3obj['audio']['sample_rate'];
            $F['sr'] = number_format($v/1000,1) . "k";
        }

        if( !empty($id3obj['audio']['channelmode']) )
        {
            $v = $id3obj['audio']['channelmode'];
            $F['ch'] = $v;
        }

        if( !empty($id3obj['video']['resolution_x']) )
        {
            $F['dim'] = array( $id3obj['video']['resolution_x'],
                                $id3obj['video']['resolution_y'] );
        }

        if( !empty($id3obj['playtime_string']) )
        {
            $v = $id3obj['playtime_string'];
            if( $v == '0:00' )
                $v = '0:01';
            $F['ps'] = $v;
        }

        if( !empty($id3obj['bitrate']) )
        {
            if( !empty($id3obj['audio']['bitrate_mode']) )
            {
                if( $id3obj['audio']['bitrate_mode'] == 'vbr') 
                    $F['br'] = 'VBR';
                elseif( $id3obj['audio']['bitrate_mode'] == 'cbr') 
                    $F['br'] = 'CBR';
            }
            else
            {
                $v = $id3obj['bitrate'];
                $F['br'] = number_format(($v/1000)) . "kbps";
            }
        }
        
        if( !empty($id3obj['zip'])  )
        {   
            $files = $id3obj['zip']['files']; 
            $this->_walk_zip_files(null,$files,"",$zipdir);
            $F['zipdir'] = $zipdir;
        }

    }

    // Build an array with a listing of files contained
    // in the zip file
    function _walk_zip_files($k,$v,$curdir,&$R)
    {
        if( is_array($v) )
        {
            foreach( $v as $k2 => $v2 )
                $this->_walk_zip_files($k2,$v2,"$curdir$k/",$R);
        }
        else
        {
            if( $v > CC_1MG )
                $v = number_format($v/CC_1MG,2) . "MB";
            elseif ( $v > 1000 )
                $v = number_format($v/1024,2) . "kb";

            $R['files'][] = "$curdir$k  ($v)";
        }
    }

    function _parse_format_name($A)
    {
        $media_type = null;

        if( isset($A['video']) )
        {
            if( isset($A['playtime_string']) )
                $media_type = 'video';
            else
                $media_type = 'image';

            $dataformat = $A['video']['dataformat'];
        }
        elseif( isset($A['audio']) )
        {
            $media_type = 'audio';
            $dataformat = $A['audio']['dataformat'];
        }
        elseif( isset($A['zip']) )
        {
            $media_type = 'archive';
            $dataformat = '';
        }

        $name = null;
        if( $media_type && isset($A['fileformat']) )
            $name = "$media_type-{$A['fileformat']}-$dataformat";

        return($name);
    }

}

?>