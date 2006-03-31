<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU, array( 'CCSearch', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCSearch', 'OnMapUrls') );

class CCSearchForm extends CCForm
{
    function CCSearchForm()
    {
        $this->CCForm();
        $fields = array( 
                'search_text' =>
                        array( 'label'      => 'Search Text',
                               'form_tip'   => '',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED),
                'search_type' =>
                        array( 'label'      => 'Type',
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => array( 'any' => 'Any match',
                                                      'all' => 'Match all',
                                                       'phrase' => "Exact Phrase" ),
                               'flags'      => CCFF_POPULATE),
                'search_in' =>
                        array( 'label'      => 'What',
                               'form_tip'   => '',
                               'formatter'  => 'select',
                               'options'    => array( CC_SEARCH_ALL     => 'Entire Site',
                                                      CC_SEARCH_UPLOADS => 'Uploads',
                                                      CC_SEARCH_USERS   => "Users" ),
                               'flags'      => CCFF_POPULATE),
                        );

        $this->AddFormFields( $fields );
        $this->SetSubmitText('Search');
        $this->SetHandler( ccl('search', 'results') );
    }
}

class CCSearch
{
    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
        'search'   => array( 'menu_text'  => 'Search',
                         'menu_group' => 'visitor',
                         'weight' => 14,
                         'action' =>  ccl('search'),
                         'access' => CC_DONT_CARE_LOGGED_IN
                         )
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
        CCEvents::MapUrl( 'search',         array('CCSearch','Search'),       CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'search/people',  array('CCSearch','OnUserSearch'), CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'search/results', array('CCSearch','OnSearch'),     CC_DONT_CARE_LOGGED_IN );
    }

    function Search()
    {
        CCPage::SetTitle("Search");
        $form = new CCSearchForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnSearch()
    {
        CCPage::SetTitle('Search Results');

        $query = CCUtil::StripText($_POST['search_text']);
        if( empty($query) )
        {
            $this->Search();
            return;
        }
        $type = CCUtil::StripText($_POST['search_type']);
        $what = intval($_POST['search_in']);

        $results = array();
        $this->DoSearch($query,$type,$what,$results);

        if( !empty($results[CC_SEARCH_UPLOADS]) )
        {
            CCPage::PageArg('upload_search_results',
                           $results[CC_SEARCH_UPLOADS],'search.xml/upload_search_results');
        }
        if( !empty($results[CC_SEARCH_USERS]) )
        {
            CCPage::PageArg('user_search_results',
                         $results[CC_SEARCH_USERS],'search.xml/user_search_results');
        }
    }

    function DoSearch($query,$type,$what,&$results)
    {
        if( empty($query) )
            return( null );

        $query = addslashes($query);
        $qlower = strtolower($query);
        if( $type == 'phrase' )
            $terms = array( $qlower );
        else
            $terms = preg_split('/\s+/',$qlower);

        if( $what & CC_SEARCH_UPLOADS )
        {
            $fields = array( 'upload_name', 'upload_file_name', 'upload_description',
                              'user_name', 'user_real_name');

            $filter = CCSearch::BuildFilter($fields, $qlower, $type);
            $uploads =& CCUploads::GetTable();
            $up_results = $uploads->GetRecords($filter);
            $count = count($up_results);
            for( $i = 0; $i < $count; $i++ )
            {
                $extra = '';
                foreach( $fields as $field )
                    $extra .= $up_results[$i][$field] . ' ';
                $up_results[$i]['result_info'] = CCSearch::_highlight_results($extra,$terms);

                $up_results[$i]['local_menu'] = 
                                   CCMenu::GetLocalMenu(CC_EVENT_UPLOAD_MENU,array(&$up_results[$i]));

            }
            $results[CC_SEARCH_UPLOADS] =& $up_results;
        }

        if( $what & CC_SEARCH_USERS )
        {
            $fields = array( 'user_description', 'user_homepage', 'user_name', 'user_real_name');

            $filter = CCSearch::BuildFilter($fields, $qlower, $type);
            $users  =& CCUsers::GetTable();
            $user_results = $users->GetRecords($filter);
            $count = count($user_results);
            for( $i = 0; $i < $count; $i++ )
            {
                $extra = '';
                foreach( $fields as $field )
                    $extra .= $user_results[$i][$field] . ' ';
                $user_results[$i]['result_info'] = CCSearch::_highlight_results($extra,$terms);
            }
 
            $results[CC_SEARCH_USERS] = $user_results;
        }

    }

    function OnUserSearch($field,$tag)
    {
        $field = 'user_' . $field;
        CCPage::SetTitle("Users That Mentioned '$tag'");
        CCUser::ListRecords( "$field LIKE '%$tag%'" );
    }


    function _highlight_results($input,&$terms,$maxoutlen = 100)
    {
        $max = $maxoutlen;

        // stripos is only on PHP 5 so we have to fake it...
        $xcopy = strtolower($input);
        foreach( $terms as $term )
        {
            if( !$term )
                continue;
            $pos = strpos($xcopy,$term);
            if( $pos !== false )
            {
                $len   = strlen($term);
                $term  = substr($input,$pos,$len); // get mixed version of term
                $repl  = "<span>$term</span>";
                $temp  = substr_replace($input, $repl, $pos, $len);
                if( $pos + $len + 20 > $max )
                    $temp = "..." . substr($temp,$pos-20,$max-5);
                $input = $temp;
                break;
            }
        }
        if( strlen($input) > $max )
            $input = substr($input,0,$max) . '...';

        return($input);
    }

    //
    // $fields     - array of fields (e.g. array( "aboutme", "whatilike" )
    // $searchterm - string (e.g. "Elivs Presley") 
    // $searchtype - 'phrase' match exact phrase
    //               'all'    match all
    //               'any'    mantch any 
    // 
    // returns string to be used in WHERE clause of SQL statement,
    //

    function BuildFilter( $fields, $searchterm, $searchType = "any" )
    {
        $fieldlist  = implode(",",$fields);
        if( count($fields) > 1 )
            $field = "LOWER(CONCAT_WS(' ',$fieldlist))";
        else
            $field = "LOWER($fieldlist)";

        $terms = preg_split("/[\s,+]+/",$searchterm);
        if( empty($terms) )
            return;

        if( ($searchType == 'phrase') || (count($terms) == 1) )
            return( " ($field LIKE '%$searchterm%') ");

        $fields = array_fill(0,count($terms),$field);
        $terms  = array_map("_search_wrap_like",$terms,$fields);
        $OP     = $searchType == 'all' ? " AND " : " OR ";
        $filter = implode($OP,$terms);

        return( "($filter)" );
    }


    function _build_date_filter($datestart,$dateend,$datefield)
    {
        $datestart = trim($datestart);
        $dateend   = trim($dateend);
        $s = '';
        if( $datestart )
        {
            if( !$dateend || ($datestart == $dateend) )
            {
                $s = " ( $datefield LIKE '$datestart%') ";
            }
            else
            {
                $datestart .= " 00:00:00";
                $dateend   .= " 00:00:00";
                $s = " ( ($datefield >= '$datestart') AND ($datefield <= '$dateend') )";
            }
        }

        if( !$s )
            $s = " 1 ";

        return($s);
    }

}

function _search_wrap_like($term,$field)
{
    $term = addslashes($term);
    return( "\n  ($field LIKE '%$term%')" );
}


if (!function_exists('array_fill')) 
{
    function array_fill($iStart, $iLen, $vValue) {
       $aResult = array();
       for ($iCount = $iStart; $iCount < $iLen + $iStart; $iCount++) {
           $aResult[$iCount] = $vValue;
       }
       return $aResult;
    }
}


?>