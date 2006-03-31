<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

function cc_setcookie($name,$value,$expire,$path='',$domain='')
{
    global $CC_GLOBALS;

    if( empty($path) )
        $path = '/';
    if( empty($domain) )
        $domain = $CC_GLOBALS['cookie-domain'];
    
    $ok = setcookie($name,$value,$expire,$path,$domain);
    //CCDebug::Log("Setting cookie $ok: setcookie($name,$value,$expire,$path,$domain)");

    return( $ok );
}

class CCUtil
{
    function StripText(&$text)
    {
        if( is_integer($text) )
            return($text);
        if( empty($text) )
            return(null);
        $text = trim(strip_tags(CCUtil::StripSlash($text)));
        return($text);
    }

    function StripSlash(&$mixed)
    {
        if( get_magic_quotes_gpc() == 1 )
        {
            if( is_array($mixed) )
            {
                $keys = array_keys($mixed);
                $c = count($keys);
                for( $i = 0; $i < $c; $i++ )
                    $mixed[$keys[$i]] = stripslashes($mixed[$keys[$i]]);
            }
            else
            {
                $mixed = trim(stripslashes( $mixed ));
            }
        }
        return($mixed);
    }

    function AccessError($file,$lineo)
    {
        print("Access error<br />");
        if( CCUser::IsAdmin() )
            CCDebug::StackTrace();
    }

    function SendBrowserTo($newurl='')
    {
        if( empty($newurl) )
        {
            if( !empty($_POST['http_referer']) )
                $newurl = htmlspecialchars(urldecode($_POST['http_referer']));
        }
        header("Location: $newurl");
        exit;
    }

    function IsHTTP()
    {
        return( !empty($_SERVER['HTTP_HOST']) );
    }

    function MakeSubdirs($pathname,$mode=0755)
    {
        // Check if directory already exists
        if (is_dir($pathname) || empty($pathname)) {
            return true;
        }
     
        // Ensure a file does not already exist with the same name
        if (is_file($pathname)) {
            trigger_error('MakeDubdirs() File exists', E_USER_WARNING);
            return false;
        }
     
        // Crawl up the directory tree
        $next_pathname = substr($pathname, 0, strrpos($pathname, '/'));
        if (CCUtil::MakeSubdirs($next_pathname, $mode)) {
            if (!file_exists($pathname)) {
                return mkdir($pathname, $mode);
            }
        }
     
        return false;
    }

    function BaseFile($path)
    {
        $base = basename($path);
        $ex = explode('.',$base);
        if( count($ex) > 1 )
            $base = basename($path, '.' . $ex[ count($ex)-1 ]);
        return($base);
    }

    function LegalFileName($name_to_cleans)
    {
        $goodchars = preg_quote('a-zA-Z0-9.(_)-');
        return( preg_replace("/[^$goodchars]/",'_',substr($name_to_cleans,0,45)) );
    }
}



?>