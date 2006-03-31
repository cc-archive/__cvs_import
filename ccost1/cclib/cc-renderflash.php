<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,   array( 'CCRenderFlash', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRenderFlash', 'OnMapUrls'));

class CCRenderFlash
{

    function Play($username,$upload_id)
    {
        $uploads =& CCUploads::GetTable();
        $record =& $uploads->GetRecordFromID($upload_id);
        $url = $record['download_url'];
        list( $w, $h ) = CCUploads::GetFormatInfo($record,'dim');
        $html =<<<END
<html>
<body style="margin:0">
<object width="$w" height="$h" classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0">
<param name="movie" value="$url">
<embed src="$url" width="$w" height="$h" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" >
</embed>
</object></body>
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
        if( CCUploads::IsMediaType($record,'video','swf')  )
        {
            $link = ccl('media','playflash', $record['user_name'],
                                             $record['upload_id']);
            list( $w, $h ) = CCUploads::GetFormatInfo($record,'dim');
            $w += 30;
            $h += 30;
            $action =<<<END
      javascript:window.open('$link','flashplay',
                'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=$w, height=$h');
END;
            $menu['playflash'] = 
                         array(  'menu_text'  => 'Play',
                                 'weight'     => -10,
                                 'id'         => 'playflash',
                                 'access'     => CC_DONT_CARE_LOGGED_IN,
                                 'scriptaction'     =>  $action );

        }
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'media/playflash',   array('CCRenderFlash', 'Play'), CC_DONT_CARE_LOGGED_IN);
    }

}


?>