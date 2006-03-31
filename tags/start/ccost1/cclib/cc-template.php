<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCTemplateAdmin', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCTemplateAdmin', 'OnMapUrls'));

function cc_init_template_lib()
{
    static $_init = 0;
    if( !empty($_init) )
        return;
    
    $_init = true;

    global $CC_GLOBALS;

    set_include_path(get_include_path() . PATH_SEPARATOR . $CC_GLOBALS['php-tal-dir'] );

    define('PHPTAL_CACHE_DIR', $CC_GLOBALS['php-tal-cache-dir'] . '/') ;
    define('PHPTAL_NO_CACHE', true) ;
    require_once($CC_GLOBALS['php-tal-dir'] . "/PHPTAL.php"); 

    class CCCleanTemplateFilter extends PHPTAL_Filter 
    { 
        // note: the docs are just wrong about the signature for this
        // method as an input filter
        function filter($data) // &$tpl, $data, $mode) 
        { 
            // replace all php calls to non-CC functions with CC_badcall API
            //
            $regex = "/(php:*\W+)(([\w]+::)?(?!CC_)([\w]+))\([^\)]*\)/iU";
            $repl  = "$1 CC_badcall('$2')";
            return(preg_replace($regex, $repl, $data));
        } 
    }

}

class CCTemplate
{
    var $_template_file;
    var $_html_mode;
    var $_template;

    function CCTemplate($template_file, $html_mode = true)
    {
        $this->_template_file = $template_file;
        $this->_html_mode     = $html_mode;
    }

    function _init_lib()
    {
        cc_init_template_lib();
        if( empty($this->_template) )
        {
            $this->_template = new PHPTAL($this->_template_file);
            $this->_template->addInputFilter(new CCCleanTemplateFilter()); 
            $this->_template->setOutputMode($this->_html_mode ? PHPTAL_XHTML : PHPTAL_XML );
        }
    }


    function SetAllAndPrint( $args, $admin_dump = false )
    {
        $this->SetAllAndParse( $args, true, $admin_dump );
    }

    function SetAllAndParse( $args, $doprint = false, $admin_dump = false )
    {
        $this->_init_lib();
        $this->_template->setAll($args);
        $res = $this->_template->execute();
        if( PEAR::isError($res) )
        {
            if( $admin_dump )
            {
                print("<pre >");
                print_r($res);
                print("</pre>");
            }
            else
            {
                 print("There an error rendering this page.");
            }
        }
        else
        {
            if( $doprint )
                print($res);
            else
                return($res);
        }
    }
}

class CCAdminTemplateMacrosForm extends CCEditConfigForm
{
    function CCAdminTemplateMacrosForm()
    {
        global $CC_GLOBALS;

        $this->CCEditConfigForm('tmacs');

        $text = file_get_contents( $CC_GLOBALS['template-root'] . '/custom.xml' );
        preg_match_all( '/define-macro="([^"]*)"/s', $text, $m );
        foreach($m[1] as $T)
        {
            $fields[$T] = array( 'label'     => str_replace('_',' ',$T),
                                 'formatter' => 'checkbox',
                                 'flags'     => CCFF_POPULATE );
        }
        $this->AddFormFields($fields);
        $this->SetHelpText("Pick which UI elements should appear on every page. " .
                           "(These are template macros found in <b>custom.xml</b> .)");
    }
}

class CCAdminTemplateTagsForm extends CCEditConfigForm
{
    function CCAdminTemplateTagsForm()
    {
        $this->CCEditConfigForm('ttag');

        $configs =& CCConfigs::GetTable();
        $ttags = $configs->GetConfig('ttag');

        $fields = array();
        foreach( $ttags as $K => $V )
        {
            $fields[$K] =
                       array(  'label'       => $K,
                               'form_tip'    => '',
                               'formatter'   => strlen($V) > 80 ? 'textarea' : 'textedit',
                               'value'       => htmlspecialchars($V),
                               'flags'       => CCFF_NOSTRIP );
        }
        $this->AddFormFields($fields);
        $this->SetSubmitText("Submit Changes");
        $newtaglink = ccl('admin', 'templatetags', 'new' );
        $this->SetHelpText("These values are used on each page on the site. If you have customized the ".
                           "templates you can create new tags by <a href=\"$newtaglink\">clicking here</a>.");
    }
}

class CCNewTemplateTagForm extends CCForm
{
    function CCNewTemplateTagForm()
    {
        $this->CCForm();

        $fields['newtag'] =
                   array(  'label'       => 'Tag Name',
                           'formatter'   => 'textedit',
                           'flags'       => CCFF_REQUIRED | CCFF_NOUPDATE) ;

        $this->AddFormFields($fields);
    }
}

class CCTemplateAdmin
{
    function OnAdminContent()
    {
        CCPage::SetTitle("Include Specialized Content");
        $form = new CCAdminTemplateMacrosForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnPeopleCustomize($username)
    {
        if( empty($_POST) )
        {
            CCPage::SetTitle("Custom Skin");
            $form = new CCPickStyleSheetForm();
            $form->SetHandler( ccl('people','customize',$username) ); // otherwise it's global
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $css = CCUtil::StripText($_POST['style-sheet']);
            
            cc_setcookie('style-sheet-' . CCUser::CurrentUserName(),$css,time() + (60*60*24*30) );

            if( empty($_POST['http_referer']) )
                CCPage::SetStyleSheet($css);
            else
                CCUtil::SendBrowserTo();
        }
    }

    function OnAdminTags()
    {
        CCPage::SetTitle("Edit Template Tags");
        $form = new CCAdminTemplateTagsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    function OnNewTags()
    {
        $form = new CCNewTemplateTagForm();
        if( empty($_POST['newtemplatetag']) )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $newtagname = $_REQUEST['newtag'];
            $form->SetHiddenField($newtagname,'');
            CCAdmin::SaveConfig($form);
        }
    }

    function GetTemplates($prefix,$ext)
    {
        global $CC_GLOBALS;
    
        $dir = $CC_GLOBALS['template-root'];
        $files = array();
        if ($dh = opendir($dir)) 
        {
            while (($file = readdir($dh)) !== false) 
            {
                if( preg_match( "/$prefix-(.*).$ext/", $file, $m ) )
                {
                    $files[ $dir . $file ] = $m[1];
                }
            }
            closedir($dh);
        }

        return( $files );
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'admintemplate'   => array( 'menu_text'  => 'Template Tags',
                             'menu_group' => 'configure',
                             'weight' => 50,
                             'action' =>  ccl('admin','templatetags'),
                             'access' => CC_ADMIN_ONLY
                             ),
            /*
            'usercss'   => array( 'menu_text'  => 'Your Skin',
                             'menu_group' => 'configure',
                             'weight' => 50,
                             'action' =>  ccl('people','customize',CCUser::CurrentUserName()),
                             'access' => CC_MUST_BE_LOGGED_IN
                             ),
            */
            'templatemacs'  => array( 'menu_text'  => 'Page Content',
                             'menu_group' => 'configure',
                             'weight' => 53,
                             'action' =>  ccl('admin','content'),
                             'access' => CC_ADMIN_ONLY
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
        CCEvents::MapUrl( 'admin/templatetags',     array('CCTemplateAdmin','OnAdminTags'),         CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/content',          array('CCTemplateAdmin','OnAdminContent'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/templatetags/new', array('CCTemplateAdmin','OnNewTags'),           CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'people/customize',       array('CCTemplateAdmin','OnPeopleCustomize'),   CC_MUST_BE_LOGGED_IN);
    }

}

?>