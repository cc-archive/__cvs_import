<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_UPLOAD_MENU,   array( 'CCRenderAudio', 'OnUploadMenu'));
CCEvents::AddHandler(CC_EVENT_CONTEST_MENU,  array( 'CCRenderAudio', 'OnContestMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,      array( 'CCRenderAudio', 'OnMapUrls'));


class CCRenderAudio
{

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'contests/streamsource', array('CCRenderAudio', 'StreamContestSource'),  CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'contests/stream',       array('CCRenderAudio', 'StreamContest'),        CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'files/stream',          array('CCRenderAudio', 'StreamFiles'),          CC_DONT_CARE_LOGGED_IN );
    }

    /**
    * Event handler for building local menus for contest rows
    *
    * @see CCMenu::AddItems
    */
    function OnContestMenu(&$menu,&$record)
    {
        // if( CCUploads::IsMediaType($record,'audio') )

        $contest = $record['contest_short_name'];

        $menu['streamcontestsource'] = 
                 array(  'menu_text'  => 'Stream Source',
                         'weight'     => -10,
                         'id'         => 'streamcontestsource',
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => ccl( 'contests', 'streamsource', $contest . '.m3u' ) );

        if( CCUser::IsAdmin() || $record['contest_can_browse_entries'] )
        {
            $menu['streamcontest'] = 
                 array(  'menu_text'  => 'Stream Entries',
                         'weight'     => -2,
                         'id'         => 'streamcontestentries',
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => ccl( 'contests', 'stream', $contest. '.m3u' ) );
        }
    }

// dcmitype

    function OnUploadMenu(&$menu,&$record)
    {
        if( !CCUploads::IsMediaType($record,'audio') )
            return;

        if( empty($record['contest_id']) )
            $fakename = $record['user_name'];
        else
            $fakename = $record['contest_short_name'];

        $menu['stream'] = 
                 array(  'menu_text'  => 'Stream',
                         'weight'     => -10,
                         'id'         => 'streamcontestsource',
                         'access'     => CC_DONT_CARE_LOGGED_IN,
                         'action'     => ccl( 'files', 'stream', 
                                            $fakename, 
                                            $record['upload_id']. '.m3u' ) );
    }


    function StreamContestSource($contest_with_m3u)
    {
        $arr = split('.',$contest_with_m3u);
        $this->_stream_contest_files($arr[0],CCUD_CONTEST_ALL_SOURCES);
    }

    function StreamContest($contest_with_m3u)
    {
        list( $contest_short_name ) = explode('.',$contest_with_m3u);
        $contests =& CCContests::GetTable();
        $record =& $contests->GetRecordFromShortName($contest_short_name);
        if( CCUser::IsAdmin() || $record['contest_can_browse_entries'] )
            $this->_stream_contest_files($contest_short_name,CCUD_CONTEST_ENTRY);
        else
            CCUtil::AccessError(__FILE__,__LINE__);
    }

    function _stream_contest_files($contest_short_name,$systags)
    {
        $contests =& CCContests::GetTable();
        $contest_id = $contests->GetIDFromShortName($contest_short_name);
        $where['upload_contest'] = $contest_id;
        $this->_stream_files($where,$systags);
    }

    function StreamFiles($user,$fileid_with_m3u)
    {
        list( $fileid ) = explode('.',$fileid_with_m3u);
        $where['upload_id'] = $fileid;
        $this->_stream_files($where,'');
    }

    function _stream_files($where,$tags)
    {
        $uploads =& CCUploads::GetTable();
        if( $tags )
            $uploads->SetTagFilter($tags);
        $records =& $uploads->GetRecords($where);
        $streamfile = '';
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            if( $uploads->IsMediaType($records[$i],'audio') )
                $streamfile .= $records[$i]['download_url'] . "\n";
        }

        header("Content-type: audio/x-mpegurl");
//      header("Content-type: text/plain");
        print($streamfile);
        exit;
    }

}


?>