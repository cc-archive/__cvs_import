<?

// $Header$

error_reporting(E_ALL);

define('IN_CC_HOST', true);

chdir('..');

$step = empty($_REQUEST['step']) ? '1' : $_REQUEST['step'];

if( $step != '5' )
    include('ccadmin/cc-install-head.php');
$stepfunc = 'step_' . $step;
$stepfunc();
print('</body></html>');


function step_1()
{
    $v = split('\.',phpversion());
    if( intval($v[0]) < 4 )
    {
        $vmsg = "<div class=\"err\">It doesn't look like you're running on PHP 4 or higher, you can't run CC Host until you upgrade.</span>";
    }
    else
    {
        $vmsg = "It looks like you're running on a supported version of PHP";
    }

    $id3suggest  = $_SERVER['DOCUMENT_ROOT'] . '/getid3';
    include('ccadmin/cc-install-intro.php');
}

function step_1a()
{
?>
<h2>A Warning</h2>

<p>If you have a previous installation of CC Host and you use the same database name as that previous installation,
this installation script with <b>completely and totally destory</b> all previous data in that database. All records
of uploads and configuation will be wiped completely out.</p>

<h3>If you're OK with that <a href="?step=2">then continue...</a></h3>
<?
}

function step_2()
{
    print('<h2>We Ask, You Answer</h2>');
    
    $v = get_default_values();
    $f = get_install_fields($v);

    print_install_form($f);
}

function step_3()
{
    $f = array();
    $errs = '';

    if( !verify_fields($f,$errs) || 
        !install_db_config($f,$errs) ||
        !install_tables($f,$errs) )
    {
        print_install_form($f,$errs);
        return;
    }

    step_3a();
}

function step_4()
{
    require_once('cc-config-db.php');
    require_once('cclib/cc-defines.php');
    require_once('cclib/cc-debug.php');
    require_once('cclib/cc-database.php');
    require_once('cclib/cc-table.php');
    require_once('cclib/cc-config.php');

    $configs =& CCConfigs::GetTable();
    $settings = $configs->GetConfig('settings');
    $admin    = $settings['admins'];
    $ttags    = $configs->GetConfig('ttag');
    $root_url = $ttags['root-url'];
    $uurl     = htmlspecialchars(urlencode($root_url));
    $config   = $configs->GetConfig('config', CC_GLOBAL_SCOPE);
    $cookdom  = htmlspecialchars(urlencode($config['cookie-domain']));

    $html =<<<END
    <h2>Hiding the Admin Steps</h2>

    <form style="display:inline" action="?step=5" method="post">
        <input type="hidden" name="hosturl" value="$uurl" />
        <input type="hidden" name="user" value="$admin" />
        <input type="hidden" name="cd"   value="$cookdom" />

    <p>You must rename the <b>/ccadmin</b> subdirectory in order to secure the site for unauthorized usage. CC Host 
    won't run until you do this.</p>

    <p>If you've done that step you can browse later to <a href="$root_url">$root_url</a> and log in as "<b>$admin</b>"
    and continue setting up and configuring the site.</p>

    </form>
END;

    print($html);
}

function step_5()
{
    include('cclib/cc-defines.php');

    clean_post();
    $cookie_com = urldecode($_POST['cd']);
    $user       = $_POST['user'];
    $url        = urldecode($_POST['hosturl']);

    //print("<pre>");print_r($_REQUEST);print("</pre>");exit;
    setcookie(CC_USER_COOKIE,$user,null,'/',$cookie_com);
    header("Location: $url");

}

function step_3a()
{
    $v['file_uploads']['v'] = ini_get('file_uploads');
    $v['file_uploads']['s'] = 'On (1)';
    $v['file_uploads']['m'] = 'This is required to be <b>On</b> to allow uploads';
    $v['file_uploads']['k'] = ($v['file_uploads']['v'] && ($v['file_uploads']['v'] != 'Off'));
    $v['file_uploads']['i'] = ' ';

    $v['upload_max_filesize']['v'] = ini_get('upload_max_filesize');
    $v['upload_max_filesize']['s'] = '10';
    $v['upload_max_filesize']['m'] = 'Determines the overall maxium file upload size. (Typical MP3 song is encoded at 1M per minute.)';
    preg_match('/([0-9]*)/',$v['upload_max_filesize']['v'],$m);
    $i = intval($m[1]);
    $v['upload_max_filesize']['i'] = $i;
    $v['upload_max_filesize']['k'] = $i < 10 ? false : true;

    $v['post_max_size']['v'] = ini_get('post_max_size');
    $v['post_max_size']['s'] = '10';
    $v['post_max_size']['m'] = 'Determines the maxium file upload size from an HTML form.';
    preg_match('/([0-9]*)/',$v['post_max_size']['v'],$m);
    $i = intval($m[1]);
    $v['post_max_size']['k'] = $i < 10 ? false : true;
    $v['post_max_size']['i'] = $i;

    $v['memory_limit']['v'] = ini_get('memory_limit');
    $v['memory_limit']['s'] = '25';
    if( $v['memory_limit']['v'] )
    {
        $v['memory_limit']['m'] = 'Dealing with large file can consume a lot of memory, being too stingy can have adverse affects.';
        preg_match('/([0-9]*)/',$v['memory_limit']['v'],$m);
        $i = intval($m[1]);
        $v['memory_limit']['k'] = $i < 25 ? false : true;
        $v['memory_limit']['i'] = $i;
    }
    else
    {
        $v['memory_limit']['m'] = '<i>It looks as though your installation of PHP is not compiled to use <a target="_blank"  href=\"http://us3.php.net/manual/en/ini.core.php#ini.memory-limit\">this setting</a>.</i>';
        $v['memory_limit']['k'] = 1;
        $v['memory_limit']['i'] = '';
    }

    $v['max_execution_time']['v'] = ini_get('max_execution_time');
    $v['max_execution_time']['s'] = '120';
    $v['max_execution_time']['m'] = 'Number of seconds a script will execute before aborting. You have to allow for users who upload large files over slow connections.';
    $i = intval($v['max_execution_time']['v']);
    $v['max_execution_time']['i'] = $i;
    $v['max_execution_time']['k'] = $i < 120 ? false : true;

    $v['max_input_time']['v'] = ini_get('max_input_time');
    $v['max_input_time']['s'] = '-1';
    $v['max_input_time']['m'] = 'Number of seconds a form\'s script will execute before aborting. You have to allow for users who upload large files over slow connections. (setting to -1 allows unlimited time)';
    $i = intval($v['max_input_time']['v']);
    $v['max_input_time']['i'] = $i;
    $v['max_input_time']['k'] = ($i > -1) && ($i < 120) ? false : true;

?>
    <h2>Setting up your PHP environment</h2>
    <p>There are several things you should know about uploading files to a PHP environment.</p>
    <p>The default settings for a PHP install may not be the ideal. A list of all PHP settings, where they can
    be changed and what version they apply to can be found <a href="http://us3.php.net/manual/en/ini.php#ini.list">here</a>.</p>
    <p>Below are some settings you should be aware of. You might want to print or save this page for future reference.</p>

    <table class="ini_table">
    <tr><th>Setting Name</th><th>Description</th><th>Current<br />Value</th><th>Suggested<br />Value</th></tr>
<?
    $html = '';
    foreach( $v as $n => $d )
    {
        $html .= "<tr><td class=\"r\"><b>$n</b></td><td>{$d['m']}</td><td class=\"c\"";
        if( !$d['k'] )
            $html .= " style=\"color:red\" ";
        $html .= ">{$d['v']}</td><td class=\"c\">{$d['s']}</td></tr>\n";
    }
    print($html);
?>
    </table>

    <h3>You're almost done, there's <a href="?step=4">one more thing...</a></h3>

<?

}

function install_tables(&$f,&$errs)
{
    //print("<pre>");print_r($f);print("</pre>");exit;
    require_once( 'cc-config-db.php');
    require_once( 'cclib/cc-defines.php');
    require_once( 'cclib/cc-debug.php');
    require_once( 'cclib/cc-database.php' );
    require_once( 'cclib/cc-table.php' );
    require_once( 'cclib/cc-config.php');
    require_once( 'ccadmin/cc-install-db.php');
    
    CCDebug::Enable(true) ;

    if( !cc_install_tables($f,$errs) )
        return(false);

    print "Created tables<br />";

    $pw = md5( $f['pw']['v'] );
    $user = $f['admin']['v'];
    $date = date('Y-m-d H:i:00');
    $sql =<<<END
        INSERT INTO cc_tbl_user (user_name,user_real_name,user_password,user_registered) VALUES         
            ('$user','$user','$pw','$date')
END;

    if( !mysql_query($sql) )
    {
        $errs = "Error creating admin account: " . mysql_error();
        return( false );
    }

    print "Created admin account <br />";

    return( true );

}

function clean_post()
{
    if( get_magic_quotes_gpc() == 1 )
    {
        $keys = array_keys($_POST);
        $c = count($keys);
        for( $i = 0; $i < $c; $i++ )
            $_POST[$keys[$i]] = trim(stripslashes( $_POST[$keys[$i]] ));
    }
    
}

function verify_fields(&$f,&$errs)
{
    clean_post();

    $f = get_install_fields($_POST);

    $ok = true;

    foreach( $f as $id => $data )
    {
        if( empty($f[$id]['v']) && $f[$id]['q'] )
        {
            $ok = false;
            $f[$id]['e'] = 'Must be filled in:';
        }
    }

    verify_mysql($f,$ok);
    verify_getid3($f,$ok);
    verify_phpbb2($f,$ok);

    $f['rooturl']['v']     = empty($f['rooturl']['v']) ? ''     : check_dir($f['rooturl']['v'],     false);
    $f['getid3']['v']      = empty($f['getid3']['v']) ? ''      : check_dir($f['getid3']['v'],      false);
    $f['phpbb2_path']['v'] = empty($f['phpbb2_path']['v']) ? '' : check_dir($f['phpbb2_path']['v'], true);

    if( !$ok )
        $errs = 'There were problems, please correct them below';

    return($ok);

}

function verify_phpbb2(&$f,&$ok)
{
    if( !empty($f['phpbb2_enabled']['v']) )
    {
        if( empty($f['phpbb2_path']['v']) )
        {
            $f['phpbb2_path']['e'] = "Must be filled in:";
            $ok = false;
        }
        elseif( !file_exists($f['phpbb2_path']['v']) )
        {
            $f['phpbb2_path']['e'] = "This directory does not exist:";
            $ok = false;
        }

        if( empty($f['phpbb2_forum_id']['v']) )
        {
            $f['phpbb2_forum_id']['e'] = "You must specify a forum (by ID number) to use";
            $ok = false;
        }
        elseif( intval($f['phpbb2_forum_id']['v']) <= 0 )
        {
            $f['phpbb2_forum_id']['e'] = "You must specify a forum (by ID number) to use";
            $ok = false;
        }

        if( empty($f['phpbb2_admin_username']['v']) )
        {
            $f['phpbb2_admin_username']['e'] = "We need the name of an admin account for phpBB2";
            $ok = false;
        }
    }
}

function verify_getid3(&$f,&$ok)
{
    if( !empty($f['getid3']['v'] ) )
    {
        $dir = check_dir($f['getid3']['v'],false);

        if( !file_exists($dir) )
        {
            $ok = false;
            $f['getid3']['e'] = "GetID3 directory ($dir) does not exist";
        }
        elseif( !file_exists( $dir . '/getid3.php' ) )
        {
            $f['getid3']['e'] = "Can't find getid3.php in " . $dir;
        }
    }
}

function verify_mysql(&$f, &$ok)
{
    $link = 0;
    if( !empty($f['dbuser']['v'] ) && !empty($f['dbpw']['v']) ) 
    {
        $link = @mysql_connect( 'localhost', $f['dbuser']['v'], $f['dbpw']['v'] );

        if( !$link )
        {
            $f['dbuser']['e'] = 'MySQL Error: ' . mysql_error();
            $ok = false;
        }
    }

    if( $link && !empty($f['database']['v']) )
    {
        if( !@mysql_select_db($f['database']['v']) )
        {
            $f['database']['e'] = "MySQL Error: " . mysql_error();
            $ok = false;
        }
        else
        {
            if( !mysql_query("CREATE TABLE table_test ( test_column int(1) )") )
            {
                $ok = false;
                $f['database']['e'] = "MySQL Error: " . mysql_error();
            }
            else
            {
                $table_ok = false;
                $qr = mysql_query("SHOW TABLES");
                $row = mysql_fetch_row($qr);
                if( $row[0] == 'table_test' )
                {
                    $qr = mysql_query("DESCRIBE table_test");
                    $row = mysql_fetch_row($qr);
                    $ok = $table_ok = $row[0] == 'test_column';
                }
                if( !$table_ok )
                {
                    $f['database']['e'] = "Error creating tables: " . mysql_error();
                }
                mysql_query("DROP TABLE table_test");
            }
        }
    }

    if( $link )
        @mysql_close($link);
}

function check_dir($dir,$slash_required)
{
    $dir = str_replace('\\','/',$dir);
    if( preg_match('#^(.*)/$#',$dir,$m) )
    {
        if( $slash_required )
            return($dir);
        return( $m[1] );
    }
    if( $slash_required )
        return( $dir . '/' );
    return( $dir );
}


function get_default_values()
{
    $v['getid3'] = route_around('getid3');

    if( !file_exists($v['getid3'] . '/getid3.php') )
        if( file_exists($v['getid3'] . '/getid3/getid3.php') )
            $v['getid3'] .= '/getid3'; 

    $v['sitename']   = 'CC Host';
    $v['cookiedom']  = $_SERVER['HTTP_HOST'];
    $v['rooturl']    = substr( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] , 0, -strlen('/ccadmin/index.php') );
    $v['dbserver']   = 'localhost';
    $v['admin']      = 'admin';
    $v['site-description'] = 'Download, Sample, Cut-up, Share.';

    if( file_exists('phpBB2') )
    {
        $v['phpbb2_enabled'] = 'checked';
        $v['phpbb2_path'] = 'phpBB2/';
    }

    return($v);
}

function get_install_fields($values)
{
    $f = array(
    'sitename'    => array( 'n' => 'Site Name',              'e' => '', 't' => 'text', 'v' => '' , 'q' => 0,
        'h' => 'The name of your site' ),

    'site-description'    => array( 'n' => 'Site Description', 'e' => '', 't' => 'text', 'v' => '' , 'q' => 0,
        'h' => 'A short tag-line for the site' ),

    'rooturl'     => array( 'n' => 'Root URL',               'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'The URL of your main installation' ),

    'admin'       => array( 'n' => 'Admin name',             'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'A CC Host account will be created with this name' ),

    'pw'          => array( 'n' => 'Admin password',         'e' => '', 't' => 'password', 'v' => '' , 'q' => 1,
        'h' => '(Remember this, you\'ll need it.)' ),

    'database'    => array( 'n' => 'Database name',          'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'Name of the mySQL database to use (this must exist already)' ),

    'dbuser'      => array( 'n' => 'Database user',          'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'mySQL account name to use to access the database' ),

    'dbpw'        => array( 'n' => 'Database password',      'e' => '', 't' => 'password', 'v' => '' , 'q' => 1,
        'h' => 'Password for the mySQL database account ' ),

    'dbserver'    => array( 'n' => 'Database server',        'e' => '', 't' => 'text', 'v' => '' , 'q' => 1,
        'h' => 'Almost always \'localhost\'' ),

    'getid3'      => array( 'n' => 'Path to GetID3',         'e' => '', 't'  => 'text', 'v' => '' , 'q' => 0,
        'h' => 'Root directory of GetID3 Library (the one with getid3.php in it)' ),

    'cookiedom'   => array( 'n' => 'Cookie Domain',          'e' => '', 't'  => 'text', 'v' => '' , 'q' => 1,
        'h' => 'If you are running on a local installation (e.g. \'localhost\') you may have to put a dot (.) in front of this name.' ),
     
    'phpbb2_enabled'        => array( 'n' => 'Enabled phpBB',         'e' => '', 't'  => 'checkbox', 'v' => '' , 'q' => 0,
        'h' => 'Check this if you have phpBB2 installed and want to integrate with CC Host (you can do this later too)' ),

    'phpbb2_path'      => array( 'n' => 'Path to phpBB ',        'e' => '', 't'  => 'text', 'v' => '' ,  'q' => 0,
        'h' => 'Root path of phpBB2 installation' ),

    'phpbb2_forum_id'       => array( 'n' => 'Review Forum ID',       'e' => '', 't'  => 'text', 'v' => '' ,  'q' => 0,
        'h' => 'This is the <b>number</b> of the forum to use to put new threads' ),

    'phpbb2_admin_username' => array( 'n' => 'phpBB Admin Username',  'e' => '', 't'  => 'text', 'v' => '' ,  'q' => 0,
        'h' => 'We need to use an admin account for your phpBB installation' ),

    );
                                   
                                      
    foreach($values as $n => $v )
    {
        $f[$n]['v'] = $v;
    }

    return($f);
}

function print_install_form($f,$err='')
{
    $fields = '';
    foreach( $f as $id => $data )
    {
        $required = $data['q'] ? '<span class="rq">*</span>' : '';

        if( $data['e'] )
            $fields .= "<tr><td></td><td class=\"fe\">{$data['e']}</td></tr>\n";

        $fields .= "<tr><td class=\"fh\">$required{$data['n']}: <div class=\"ft\">{$data['h']}</div></td>".
                   "<td class=\"fv\"><input type=\"{$data['t']}\" " .
                   "id=\"$id\" name=\"$id\" ";

        if( $data['t'] == 'checkbox' )
        {
            if( $data['v'] )
                $fields .= " checked=\"checked\" ";
        }
        else
        {
            $fields .= "value=\"{$data['v']}\" ";
        }
        
        $fields .= "/></td></tr>\n";
    }
    if( $err )
        $err = "<div class=\"err\">$err</div>";

    $html =<<<END
$err
<div class="rqmsg">Fields marked '*' are required</div>
<form action="?step=3" method="post">
<table>
     $fields
<tr><td></td><td><input type="submit" value="Continue &gt;&gt;&gt;" /></td>
</table>
</form>
END;

    print($html);
}

function install_db_config($f,&$err)
{
    $varname = "\$CC_DB_CONFIG";
    $text = "<?";
    $text .= <<<END
        
// This file is generated as part of install and config editing

if( !defined('IN_CC_HOST') )
    die('Welcome to CC Host');

$varname = array (
   'db-name'     =>   '{$f['database']['v']}',
   'db-server'   =>   '{$f['dbserver']['v']}',
   'db-user'     =>   '{$f['dbuser']['v']}',
   'db-password' =>   '{$f['dbpw']['v']}',
 
  ); 

END;

    $text .= "?>";

    $err = '';
    $fh = @fopen('cc-config-db.php','w+');
    if( !$fh )
    {
        $err = "Could not open a configuration file for writing in CC Host directory.  Please make sure the directory is writable and try again.";
    }
    else
    {
        if( fwrite($fh,$text) === false )
        {
            $err = "Could not write to configuration file in CC Host directory. Please make sure the directory is writable and try again.";
        }

        fclose($fh);
    }

    if( !$err )
        print("Database config written<br />");

    return( empty($err) );
}

function route_around($dir)
{
    if( file_exists($dir) )
        return($dir);

    if( file_exists( '../' . $dir ) )
        return( realpath( '../' . $dir ) );

    if( file_exists( '../../' . $dir ) )
        return( realpath( '../../' . $dir ) );

    return( null );
}
?>