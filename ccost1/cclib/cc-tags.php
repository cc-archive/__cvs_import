<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,   array( 'CCTag', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCTag', 'OnMapUrls'));

/**
 * Table wrapper for user and system attribute tagging
 *
 *  Just to confuse things there are two types of 'tags':
 *
 *   <ul><li>ID3 tags that are read from and stamped into and from files (like MP3s)
 *         This class has nothing to do with that</li>
 *     
 *        <li>Tags as in del.io.us and flikr where an item is catalogued
 *         according to some attributes. The attributes are searchable
 *         across the system to find 'like' items. This class is for
 *         facilitating this kind of 'tag'.</li>
 *   </ul>
*/
class CCTags extends CCTable
{
    function CCTags()
    {
        $this->CCTable('cc_tbl_tags','tags_tag');
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
            $_table = new CCTags();
        return( $_table );
    }

    function Replace($old_tags,$new_tags)
    {
        if( !is_array($new_tags) )
            $new_tags = CCTag::TagSplit($new_tags);
        if( !is_array($old_tags) )
            $old_tags = CCTag::TagSplit($old_tags);

        $tossed   = array_diff($old_tags,$new_tags);
        $used     = array_diff($new_tags,$old_tags);

        $this->UpdateOrAdd($used);
        $this->TagDelete($tossed);
    }

    function UpdateOrAdd($tags)
    {
        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);
        if( empty($tags) )
            return;

        $where = $this->GetWhereFilter($tags);
        $qr = $this->Query($where);
        $diff_tags = array();
        while( $row = mysql_fetch_array($qr) )
            $diff_tags[] = $row['tags_tag'];
        $new_tags = array_diff($tags,$diff_tags);

        $this->TagUpdate($diff_tags);
        $this->Add($new_tags);
    }

    function TagDelete($tags)
    {
        if( empty($tags) )
            return;
        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);
        $where = $this->GetWhereFilter($tags);
        $updates['tags_count'] = 'tags_count - 1';
        $this->UpdateWhere($updates,$where,false);
        $this->DeleteWhere('tags_count = 0');
    }

    function TagUpdate($tags)
    {
        if( empty($tags) )
            return;

        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);
        $where = $this->GetWhereFilter($tags);
        $updates['tags_count'] = 'tags_count + 1';
        $this->UpdateWhere($updates,$where,false);
    }

    function Add($new_tags)
    {
        if( empty($new_tags) )
            return;
        if( !is_array($new_tags) )
            $tags = CCTag::TagSplit($new_tags);

        $values = array();
        foreach( $new_tags as $tag )
        {
            $values[] = array( $tag, 1 );
        }
        $fields = array( 'tags_tag', 'tags_count' );
        $this->InsertBatch($fields,$values);
    }

    function PrepForDB($tags)
    {
        if( empty($tags) )
            return('');

        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);
        $tags = array_unique($tags);
        $tags2 = array();
        foreach( $tags as $tag )
        {
            if( empty($tag) )
                continue;
            $tag = preg_replace('/[^\w_]+/','_',$tag);
            if( strlen($tag) < 3 )
                continue;
            if( strlen($tag) > 25 )
                $tag = substr($tag,0,25);
            if( !in_array( $tag, $tags2 ) )
                $tags2[] = $tag;
        }
        return(implode(', ', $tags2));
    }

    function GetWhereFilter($tags)
    {
        if( !is_array($tags) )
            $tags = CCTag::TagSplit($tags);
        $where = array();
        foreach($tags as $tag)
        {
            $tag = addslashes($tag);
            $where[] = "( tags_tag = '$tag' )";
        }

        return( implode(' OR ',$where) );
    }

    function ExpandOnRow(&$row,$inkey,$baseurl,$outkey,$label='')
    {
        if( empty($row[$inkey]) )
            return;
        $tagstr = $row[$inkey];
        $tags = CCTag::TagSplit($tagstr);
        if( !empty($label) )
        {
            $count = empty($row[$outkey]) ? '0' : count($row[$outkey]);
            $outsubkey = $outkey . $count;
            $row[$outkey][$outsubkey]['label'] = $label;
        }
        foreach($tags as $tag)
        {
            $taglink = array( 'tagurl' => $baseurl . '/' . $tag,
                              'tag'    => $tag );

            if( empty($label) )
            {
                $row[$outkey][] = $taglink;
            }
            else
            {
                $row[$outkey][$outsubkey]['value'][] = $taglink;
            }
        }
    }

    function & GetRecords()
    {
        return( $this->QueryRows('') );
    }
}


/**
 * Helper API and system event watcher for attribute tags
 *
 *  Just to confuse things there are two types of 'tags':
 *
 *   <ul><li>ID3 tags that are read from and stamped into and from files (like MP3s)
 *         This class has nothing to do with that</li>
 *     
 *        <li>Tags as in del.io.us and flikr where an item is catalogued
 *         according to some attributes. The attributes are searchable
 *         across the system to find 'like' items. This class is for
 *         facilitating this kind of 'tag'.</li>
 *   </ul>
*/
class CCTag
{
    function TagSplit($tagstr)
    {
        /* $count = */ preg_match_all("/\s*([^ ,]+([^,]+[^ ,]+)?)\s*(,|$)/",$tagstr,$m);

        return($m[1]);
    }

    function InTag($needles,$haystack)
    {
        if( is_array($haystack) )
            $haystack = implode(', ',$haystack);

        $needles = preg_replace('/, ?/','|',$needles);

        $regex =  "/(^| |,)($needles)(,|\$)/";

        return( preg_match( $regex, $haystack ) );
    }

    function OnBrowseTags($tagstr='')
    {
        if( empty($tagstr) )
        {
            $this->ShowAllTags();
        }
        else
        {
            $where = CCSearch::BuildFilter(array('upload_tags'),$tagstr,'all');
            $uploads =& CCUploads::GetTable();
            $records = $uploads->GetRecords($where);
            CCPage::PageArg('tags_search_results',$records,'search.xml/tags_search_results');
            CCPage::SetTitle("Tags '$tagstr'");
        }
    }

    function ShowAllTags()
    {
        $tags =& CCTags::GetTable();
        $tags->SetSort('tags_tag');
        $records =& $tags->GetRecords();
        $count = count($records);
        for( $i = 0; $i < $count; $i++ )
        {
            $c = $records[$i]['tags_count'] - 1;
            $records[$i]['fontsize'] = 10 + intval(0.9 * $c);
            $records[$i]['tagurl'] = ccl('tags',  $records[$i]['tags_tag']);
        }

        CCPage::SetTitle("Tags");
        CCPage::PageArg('tag_array',$records,'search.xml/tags');
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'tags'   => array( 'menu_text'  => 'Browse Tags',
                             'menu_group' => 'visitor',
                             'weight' => 5,
                             'action' =>  ccl('tags'),
                             'access' => CC_DONT_CARE_LOGGED_IN
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
        CCEvents::MapUrl( 'tags', array('CCTag','OnBrowseTags'), CC_DONT_CARE_LOGGED_IN );
    }
}


?>