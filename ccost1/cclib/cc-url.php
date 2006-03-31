<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* For use in links to virtual commands in the current config environment
*
*/
function ccl()
{
    global $CC_GLOBALS;
    global $CC_CFG_ROOT;

    $arg = $CC_GLOBALS['pretty-urls'] ? '' : '?mode=';
    $args = func_get_args();
    $cmdurl = "/$CC_CFG_ROOT/" . implode('/',$args);
    return( cc_get_root_url() . $arg . $cmdurl );
}

/**
* For use in links to config roots files in the system
*
*/
function ccr()
{
    global $CC_GLOBALS;

    $arg = $CC_GLOBALS['pretty-urls'] ? '' : '?mode=';
    $args = func_get_args();
    $cmdurl = '/' . implode('/',$args);
    return( cc_get_root_url() . $arg . $cmdurl );
}


/**
* For use in links to real files in the system
*
*/
function ccd()
{
    $args = func_get_args();
    $url = implode('/',$args);
    return( cc_get_root_url()  . '/' . $url );
}

/**
* For real server paths 
*
*/
function cca()
{
    $args = func_get_args();
    $url = implode('/',$args);
    return( $_SERVER['DOCUMENT_ROOT'] . '/' . $url );
}

/**
* Internal helper for getting root pretty url
*
*/
function cc_get_root_url()
{
    static $_root_url;
    if( !isset($_root_url) )
    {
        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');
        $_root_url = $ttags['root-url'];
    }
    return( $_root_url );
}


?>