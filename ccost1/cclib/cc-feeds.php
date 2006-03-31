<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,    array( 'CCFeeds',  'OnMapUrls'));

class CCFeeds
{
    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'feed/rdf/tags',  array( 'CCFeeds', 'GenerateRDFFromTags'), CC_DONT_CARE_LOGGED_IN);
    }

    /**
    * Handler for feed/rdf/tags - returns rdf+xml feed for given tags
    *
    * @param string $tagstr Space (or '+') delimited tags to use as basis of xml feed
    */
    function GenerateRDFFromTags($tagstr)
    {
        $records =& $this->_get_tag_data($tagstr);
        if( empty($records) )
            return;

        $configs         =& CCConfigs::GetTable();
        $template_tags   = $configs->GetConfig('ttag');
        $site_title      = $template_tags['site-title'];

        $args['feed_url'] = ccl('tags',$tagstr);
        $args['channel_title'] = "$site_title ($tagstr)";
        $args['feed_subject'] = "$site_title ($tagstr)";
        $args['channel_description'] = "$tagstr Tags at $site_title";
        $args['feed_items'] = $records;

        global $CC_GLOBALS;

        $template = new CCTemplate( $CC_GLOBALS['template-root'] . 'rss_rdf.xml', false ); // false means xml mode
        header("Content-type: text/xml");
        $template->SetAllAndPrint($args,true);
        exit;
    }

    function & _get_tag_data($tagstr)
    {
        $uploads =& CCUploads::GetTable();
        $uploads->SetOffsetAndLimit(0,15);
        $uploads->SetOrder('upload_date','DESC');
        $where = CCSearch::BuildFilter(array('upload_tags'),$tagstr,'all');
        return( $uploads->GetRecords($where) );
    }
}



?>