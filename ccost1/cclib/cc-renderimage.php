<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,       array( 'CCRenderImage', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,          array( 'CCRenderImage', 'OnMapUrls'));
CCEvents::AddHandler(CC_EVENT_UPLOAD_ROW,        array( 'CCRenderImage', 'OnUploadRow'));
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS, array( 'CCRenderImage' , 'OnGetConfigFields' ));

class CCRenderImage
{

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'media/showimage', array('CCRenderImage','Show'), CC_DONT_CARE_LOGGED_IN );
    }

    function Show($username,$upload_id)
    {
        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        $url = $record['download_url'];
        $html =<<< END
<html>
<body>
<img src="$url" />
</body>
</html>
END;
        print($html);
        exit;
    }

    /**
    * Event handler for building local menus for contest rows
    *
    * @see CCMenu::AddItems
    */
    function OnContestMenu(&$menu,&$record)
    {
    }

    function OnUploadMenu(&$menu,&$record)
    {
        if( CCUploads::IsMediaType($record,'image') )
        {
            $link = ccl('media','showimage', $record['user_name'],
                                             $record['upload_id']);
            list( $w, $h ) = CCUploads::GetFormatInfo($record,'dim');
            $w += 10;
            $h += 10;
            $action =<<<END
      window.open('$link','showimage','toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=$w, height=$h');
END;
            $menu['showimage'] = 
                         array(  'menu_text'  => 'Show',
                                 'weight'     => -10,
                                 'id'         => 'showimage',
                                 'access'     => CC_DONT_CARE_LOGGED_IN,
                                 'scriptaction'     =>  $action );

        }
    }

    /**
    * Event handler for when a media record is fetched from the database 
    *
    * This will add semantic richness and make the db row display ready.
    * 
    * @see CCTable::GetRecordFromRow
    */
    function OnUploadRow(&$record)
    {
        if( !CCUploads::IsMediaType($record,'image') )
            return;
    
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');

        $maxx = empty($settings['thumbnail-x']) ? '60px' : $settings['thumbnail-x'];
        $maxy = empty($settings['thumbnail-y']) ? '60px' : $settings['thumbnail-y'];

        $record['file_macros'][]   = 'misc.xml/render_image';
        $record['thumbnail_url']   = $record['download_url'];
        $record['thumbnail_style'] = "height:$maxy;width:$maxx;";
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
            $fields['thumbnail-x'] = 
               array( 'label'       => 'Max Thumb X',
                       'formatter'  => 'textedit',
                       'class'      => 'cc_form_input_short',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

            $fields['thumbnail-y'] =
               array( 'label'       => 'Max Thumb Y',
                       'formatter'  => 'textedit',
                       'class'      => 'cc_form_input_short',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );
        }
    }
}


?>