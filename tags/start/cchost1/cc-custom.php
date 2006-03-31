<?

// $Header$

if( !defined('IN_CC_HOST') )
    die('Welcome to CC Host');

/* 
    CUSTOM TEMPLATE API 

    Methods here are called from templates/custom.xml

    It's pretty easy to break the site if you are not familiar with the
    rest of the site
*/

function CC_badcall($to)
{
    print("there was a call to \"$to\" in a template");
    exit;
}

function CC_query($tablename,$func)
{
    if( substr($tablename,0,2) != 'CC' )
        return(array());
    $table = new $tablename;
    return( $table->$func() );
}

 







?>