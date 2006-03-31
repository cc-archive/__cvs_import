<?

error_reporting(E_ALL);
define('IN_CC_HOST',true);
chdir('..');
include ('cc-config-db.php');
include ('cc-non-ui.php');

$configs =& CCConfigs::GetTable();
$cconfigs = $configs->GetConfig('config');
$settings = $configs->GetConfig('settings');
$ttags    = $configs->GetConfig('ttag');
$CONFIG = array_merge($cconfigs,$settings,$ttags);

set_include_path(get_include_path() . PATH_SEPARATOR . $CONFIG['php-tal-dir'] );
define('PHPTAL_CACHE_DIR', $CONFIG['php-tal-cache-dir'] . '/') ;
define('PHPTAL_NO_CACHE', true) ;
require_once($CONFIG['php-tal-dir'] . "/PHPTAL.php"); 

define( 'COMPILE', 1 );
define( 'GENHTML', 2 );
define( 'FIXUP', 3 );
define( 'INDEX', 4 );


$source_file = '';
$out_dir     = '';
$flags = 0;

for( $I = 0; $I < $argc; $I++ )
{
    switch( $argv[$I] )
    {
        case '/c':
            $flags = COMPILE;
            $source_file = $argv[++$I];
            break;

        case '/g':
            $flags = GENHTML;
            $source_file = $argv[++$I];
            break;

        case '/o':
            $out_dir= $argv[++$I];
            break;

        case '/x':
            $flags = FIXUP;
            break;

        case '/i':
            $flags = INDEX;
            $source_file = $argv[++$I];
            break;
    }
}

main($flags,$source_file,$out_dir);

function main($flags,$path,$outdir)
{
    global $argv;

    $help =<<<END

  Syntax: $argv[0] /c filename /o output_dir
          $argv[0] /x input_output_dir
          $argv[0] /g input_dir /o output_dir
          $argv[0] /i input_dir /o output_path

       /c Compile php source to symbol file(s) 
       /x Fixup symbol files (done after all sources have been compiled)
       /g Generate HTML from symbol files (done after fixup) 
       /i Create index of symbols
       /o Output directory 

END;

    if( !$flags )
        die("No operation specified. $help");

    if( empty($outdir) )
        die("No output directory specified $help");

    switch( $flags )
    {
        case COMPILE:
        case GENHTML:
        case INDEX:
            if( empty($path) )
            {
                print_r($argv);
                die("No input specified $help");
            }
    }

    switch( $flags )
    {
        case COMPILE:
            parse_file($path,$outdir);
            break;
        
        case FIXUP:
            fixup_files($outdir);
            break;

        case GENHTML:
            generate_html($path,$outdir);
            break;

        case INDEX:
            generate_index($path,$outdir);
            break;
    }
}

class SymCache
{
    var $files;
    var $outdir;

    function SymCache($outdir)
    {
        $this->files = array();
        $this->outdir = $outdir;
        $dh = opendir($outdir);
        while( ($file = readdir($dh)) !== false )
        {
            $file = $outdir . '/' . $file;
            if( is_sym_file($file) )
            {
                print("Caching: $file " . "\n");
                $this->AddClass($file);
            }
        }
        closedir($dh);
    }

    function GetClasses()
    {
        return( $this->files );
    }

    function FlushClasses()
    {
        $keys = array_keys($this->files);
        foreach( $keys as $key )
        {
            $text = serialize($this->files[$key]);
            $fh = fopen($this->outdir . '/' . $key . '.sym', 'w');
            fwrite($fh,$text);
            fclose($fh);
        }
    }

    function GetClass($classname)
    {
        if( array_key_exists($classname,$this->files) )
            return( $this->files[$classname] );

        $class =& $this->AddClass( $this->outdir . '/' . $classname . 'sym' );
        return( $class );
    }

    function AddClass($path,$classname='')
    {
        $text = file_get_contents($path);
        if( empty($classname) )
        {
            $class = unserialize($text);
            $classname = $class['name'];
            $this->files[$classname] = $class;
        }
        else
        {
            $this->files[$classname] = unserialize($text);
        }

        return( $this->files[$classname] );
    }

    function WireParents()
    {
        $keys = array_keys($this->files);
        foreach( $keys as $key )
        {
            $class =& $this->files[$key];
            if( empty($class['base']) )
                continue;
            if( empty($this->files[$class['base']]) )
                continue;
            $baseclass =& $this->files[$class['base']];
            $baseclass['derived'][] = $class['name'];
        }
    }

    function WireMethods()
    {
        $keys = array_keys($this->files);
        foreach( $keys as $key )
        {
            $class =& $this->files[$key];
            if( empty($class['methods']) || 
                empty($class['base'])  ||
                empty($this->files[$class['base']])
                )
            {
                continue;
            }
            $parents = array();
            $parent =& $this->files[$class['base']];
            while( $parent )
            {
                $parents[] =& $parent;
                if( empty($parent['base']) || empty($this->files[$parent['base']]) )
                    break;
                $parent =& $this->files[$parent['base']];
            }
            $mkeys = array_keys($class['methods']);
            $parent_count = count($parents);
            foreach( $mkeys as $mkey )
            {
                for($n = 0; $n < $parent_count; $n++ )
                {
                    if( !empty($parents[$n]['methods']) && 
                        array_key_exists($mkey,$parents[$n]['methods']) )
                    {
                        $class['methods'][$mkey]['derivedfrom'] = $parents[$n]['name'];
                        $parents[$n]['methods'][$mkey]['derivedat'][] = $class['name'];
                        break;
                    }
                }
            }
        }
    }
}

function generate_index($indir,$outpath)
{
    global $CONFIG;

    $cache = new SymCache($indir);

    $template = new CCAPITemplate();

    $args = $CONFIG;
    $args['style-sheet'] = 'apidoc.css';
    $args['page-title'] = "API Documenation: Index";
    $args['classes'] =& $cache->GetClasses();
    $args['macro_names'][] = 'index';

    $html = $template->SetAllAndParse($args);

    $fh = fopen($outpath, 'w');
    fwrite($fh,$html);
    fclose($fh);

    print("Generated HTML index $outpath\n" );
}

function fixup_files($outdir)
{
    $cache = new SymCache($outdir);
    $cache->WireParents();
    $cache->WireMethods();
    $cache->FlushClasses();
}

function generate_html($indir,$outdir)
{
    $dh = opendir($indir);

    while( ($file = readdir($dh)) !== false )
    {
        if( is_sym_file($file) )
            generate_html_file($indir . '/' . $file,$outdir);
    }
}

function generate_html_file($path,$outdir)
{
    global $CONFIG;

    $template = new CCAPITemplate();
    $text = file_get_contents($path);
    $class = unserialize($text);

    $args = $CONFIG;
    $args['style-sheet'] = 'apidoc.css';
    $args['page-title'] = "API Documenation for: " . $class['name'];
    $args['class'] = $class;
    $args['macro_names'][] = 'class';

    $html = $template->SetAllAndParse($args);

    $fh = fopen($outdir . '/' . $class['name'] . '.html', 'w');
    fwrite($fh,$html);
    fclose($fh);

    print("HTML generated for " . $class['name'] . "\n" );
}

function fix_up_args(&$methods)
{
    $m = count($methods);
    $keys = array_keys($methods);
    for( $n = 0; $n < $m; $n++ )
    {
        $method =& $methods[$keys[$n]];

        if( substr($method['comments'],0,3) == '/**' )
        {
            parse_comments($method,$method['comments']);
        }
        else
        {
            $method['shortdesc'] = '(undocumented method)';
            $method['description'] = '';
        }

        if( empty( $method['description'] ) )
            unset($method['description']);

        unset($method['comments']);
    }
}

function parse_comments(&$element,$ex)
{
    $regclean1 = '%^\s*/?\*(\*|/)?\s?%m';

    $regauthor =  '%^@author\s+([^$]*)$%mU';
    $regaccess = '%^@access\s+(\w+)%m';
    $regparam  =  '%^@param\s+(\w+)\s+(\$\w+)\s+([^@/]*)%m';
    $regreturn = '%^@return\s+(\w+)\s+(\$\w+)(\s+([^$]*))?$%mU';
    $regsee    =  '%^@see\s+([^$]*)$%mU';

    $ex = preg_replace($regclean1  ,"",$ex);
    $s = split("\n",$ex);
    $short = null;
    $long = '';
    foreach( $s as $line )
    {
        $trimline = trim($line);
        if( substr($trimline,0,1) == '@' )
            break;
        if( isset($short) )
        {
            $long .=  $line . "\n";
        }
        else
        {
            if( $trimline )
                $short = $trimline;
        }
    }
    
    $old = error_reporting(0);
    $coderep = "capture_highlight('\\1')";
    $long = trim(preg_replace('%<code>(.*)</code>%smUe', $coderep, $long));
    error_reporting($old);

    if( $long )
        $long = preg_replace('/^\s+$/m', "<br /><br />", $long);


    $element['shortdesc'] = $short;
    $element['description'] = $long;
    preg_match_all($regparam,$ex,$p,PREG_SET_ORDER);

    foreach( $p as $param )
    {
        if( !array_key_exists($param[2],$element['args']) )
        {
            print("Param mismatch for method: " . $element['name'] . "\n");
        }
        else
        {
            $arg =& $element['args'][$param[2]];
            $arg['type'] = $param[1];
            $arg['desc'] = $param[3];
        }
    }

    preg_match($regaccess,$ex,$a);
    if( !empty($a) )
        $element['access'] = $a[1];

    preg_match($regreturn,$ex,$r);
    if( !empty($r) )
    {
        $element['returns']['type'] = $r[1];
        $element['returns']['desc'] = $r[4];
    }

    preg_match($regsee,$ex,$seealso);
    if( !empty($seealso) )
    {
        $sees = split(',',$seealso[1]);
        foreach( $sees as $see )
        {
            if( strpos($see,'::') > 0 )
            {
                $s2 = split('::',$see);
                $element['sees'][] = array( 'href'=> $s2[0] . '.html#' . trim($s2[1]),
                                           'text'=> $see );
            }
            else
            {
                $element['sees'][] = array( 'href' => '#' . trim($see) ,
                                           'text' => $see );
            }
        }
    }

    if( empty($element['description']) || !trim($element['description']) )
        unset($element['description']);
}



function parse_file($filepath,$outdir)
{
    $filebase = basename($filepath);
    $text     = file_get_contents($filepath);
    $t        = token_get_all($text);

    $next = array( T_CLASS, T_ML_COMMENT, T_COMMENT );
    $i = 0;
    $c = count($t);
    $last_comment = '';

    while( $i < $c )
    {
        $n = next_t($t,$i,$next);
        switch($n)
        {
            case T_ML_COMMENT:
            case T_COMMENT:
            {
                $comments = $t[$i][1];
                // this next line is order dependent, otherwise $i might not be inc'd
                if( (skip_white($t,$i) == T_CLASS) && (substr($comments,0,3) == '/**') )
                    parse_class($t,$i,$comments,$filebase,$outdir);
                break;
            }
            case T_CLASS:
            {
                parse_class($t,$i,'(undocumented class)',$filebase,$outdir);
                break;
            }
        }

    }
}

function t_name($t)
{
    $n = is_array($t) ? token_name($t[0]) : $t;
    return( $n );
}


function t_get($t)
{
    if( is_array($t) )
        return($t);
    return( array( $t, $t ) );
}

function t_debug_i(&$t,$i)
{
    print("TOKEN[$i]: ");
    if( is_array($t[$i]) )
    {
        $n = token_name($t[$i][0]) ;
        print("$n (" . $t[$i][1] . ")\n" );
    }
    else
    {
        print( $t[$i] . "\n");
    }
}

function t_debug(&$t)
{
    $c = count($t);
    for( $i = 0; $i < $c; ++$i )
    {
        if( is_array($t[$i]) )
            $t[$i][0] = token_name($t[$i][0]);
    }

    print_r($t);
    if( $exit )
        exit;
}
function parse_class(&$t,&$i,$comments,$filebase,$outdir)
{
    $class = array();
    $class['file'] = $filebase;

    parse_comments($class,$comments);

    $c = count($t);
    next_t($t,$i,array( T_STRING ));
    $class['name'] = $t[$i][1];
    print("Parsing: " . $class['name'] . "\n");
    $n = next_t($t,$i,array( '{', T_EXTENDS ));
    if( $n == T_EXTENDS )
    {
        next_t($t,$i,array(T_STRING));
        $class['base'] = $t[$i][1];
        next_t($t,$i,array( '{' ));
    }

    while( $i < $c )
    {
        $n = next_t($t,$i, array( '}', T_FUNCTION, T_COMMENT, T_ML_COMMENT ));
        if( $n == '}' )
            break;
        $method = array();
        $method['comments'] = '(undocumented method)';
        if( ($n == T_COMMENT) || ($n == T_ML_COMMENT) )
        {
            $comments = $t[$i][1];
            if( substr($comments,0,3) == '/**' )
            {
                $n = skip_white($t,$i);
                if( $n == T_FUNCTION )
                    $method['comments'] = $comments;
            }
        }
        if( $n == T_FUNCTION )
        {
            next_t($t,$i,array( T_STRING ));
            $method['name'] = $t[$i][1];
            //print("METHOD: " . $method['name'] . "\n");
            $args = array();
            parse_args($t,$i,$args);
            matching_braces($t,$i);
            $method['args'] = $args;
            if( empty($args) )
                $method['noargs'] = true;
            $class['methods'][$method['name']] = $method;
        }
        ++$i;
    }

    if( !empty( $class['methods'] ) )
    {
       ksort($class['methods']);
       fix_up_args($class['methods']);
    }
    $text = serialize($class);
    $fh = fopen($outdir . '/' . $class['name'] . '.sym', 'w');
    fwrite($fh,$text);
    fclose($fh);
}

function arg($name)
{
    $this['name'] = $name;
    $this['type'] = '';
    $this['desc'] = '';
    $this['default'] = '';
    return($this);
}
function parse_args(&$t,&$i,&$args)
{
    next_t($t,$i,array( '(' ));
    $c = count($t);
    while( ++$i < count($t) )
    {
        $n = next_t($t,$i, array( T_VARIABLE, ')' ));
        if( $n == ')' )
            break;
        $arg = arg($t[$i][1]);
        $n = next_t($t,$i, array( '=', ',', ')' ));
        if( $n == '=' )
        {

            if( is_white($t,$i) )
                $n = skip_white($t,$i);
            else
                ++$i;
            $stop = array( ',', ')' );
            $value = '';
            $stack = 0;
            while( $i < $c )
            {
                list( $n, $v ) = t_get($t[$i]);
                if( in_array($n,$stop) && !$stack )
                    break;

                if( $n == ')' )
                    --$stack;
                if( $n == '(' )
                    ++$stack;

                $value .= $v;
                ++$i;
            }

            $arg['default'] = '= ' . $value;
        }
        $args[$arg['name']] = $arg;
        if( $n == ')' )
            break;
    }
    ++$i;
}

function skip_white(&$t,&$i)
{
    ++$i;
    return( skip_t($t,$i,array( T_WHITESPACE ) ) );
}

function is_white(&$t,$i)
{
    return( is_array($t[$i]) && ($t[$i][0] == T_WHITESPACE) );
}

function skip_t(&$t,&$i,$not_next)
{
    $n = '';
    $c = count($t);
    while( $i < $c )
    {
        if( is_array($t[$i]) )
            $n = $t[$i][0];
        else
            $n = $t[$i];
        if( !in_array($n,$not_next) )
            break;
        $i++;
    }
    
    return( $n );

}

function matching_braces(&$t,&$i)
{
    next_t($t,$i,array( '{', T_CURLY_OPEN ));
    $c = count($t);
    while( ++$i < count($t) )
    {
        $n = next_t($t,$i,array('}', '{', T_CURLY_OPEN));
        if( $n == '}' )
            break;
        matching_braces($t,$i);
    }
}

function next_t(&$t,&$i,$next)
{
    $n = '';
    $c = count($t);
    while( $i < $c )
    {
        if( is_array($t[$i]) )
            $n = $t[$i][0];
        else
            $n = $t[$i];
        if( in_array($n,$next) )
            break;
        $i++;
    }
    
    return( $n );
       
}

class CCAPITemplate
{
    var $_template;

    function CCAPITemplate()
    {
        global $CONFIG;

        $template = 'cctools/template-apidoc.html';
        $this->_template = new PHPTAL($template);
    }

    function SetAllAndParse($args)
    {
        $this->_template->setAll($args);
        $res = $this->_template->execute();

        if( PEAR::isError($res) )
        {
            print("<pre >");
            print_r($res);
            print("</pre>");
        }
        else
        {
            return($res);
        }
    }
}

function is_sym_file($file)
{
    return( preg_match( '/\.sym$/', $file ) );
}

function capture_highlight($str)
{
    ob_start();
    highlight_string( '<? ' . $str . ' ?>' );
    $t = ob_get_contents();
    ob_end_clean();

    return($t);
}

?>