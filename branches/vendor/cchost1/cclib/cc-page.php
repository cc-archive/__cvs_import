<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCPageAdmin', 'OnMapUrl') );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCPageAdmin', 'OnGetConfigFields') );
CCEvents::AddHandler(CC_EVENT_APP_INIT,           array( 'CCPageAdmin', 'OnAppInit') );

class CCPageAdmin
{
    function OnMapUrl()
    {
        CCEvents::MapUrl( 'viewfile', array( 'CCPageAdmin', 'ViewFile' ),  CC_DONT_CARE_LOGGED_IN );
        CCEvents::MapUrl( 'homepage', array( 'CCPageAdmin', 'Homepage' ),  CC_DONT_CARE_LOGGED_IN );
    }

    function OnAppInit()
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $homepage = $settings['homepage'];

        if( !empty($homepage) )
        {
            CCEvents::AddAlias('homepage',$homepage);
            //CCDebug::Log("Alias homepage: $homepage");
        }
    }

    function Homepage()
    {
        CCPage::SetTitle("Welcome to CC Host");
        CCPage::ViewFile('home.xml');
    }

    function ViewFile($template)
    {
        CCPage::ViewFile($template);
    }

    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings to config editing form
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope != CC_GLOBAL_SCOPE )
        {
            $fields['homepage'] =
               array(  'label'      => 'Homepage',
                       'form_tip'   => 'example: contest/mycontest<br />or: media<br />or: viewfile/home.xml',
                       'value'      => '',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE);

            $fields['style-sheet'] =
                       array( 'label'       => 'Skin Style',
                               'form_tip'   => 'Default style sheet for this view',
                               'formatter'  => 'select',
                               'options'    => CCTemplateAdmin::GetTemplates('skin','css'),
                               'flags'      => CCFF_POPULATE );

            $fields['page-template'] =
                       array( 'label'       => 'Page Template',
                               'form_tip'   => 'Default page template for this view',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED);
        }

    }
}

class CCPage extends CCTemplate
{
    var $_page_args;
    var $_body_template;

    function CCPage()
    {
        global $CC_GLOBALS;

        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('settings');
        $this->CCTemplate( $CC_GLOBALS['template-root'] . $settings['page-template'] );
        $this->_page_args = $configs->GetConfig('ttag');
    }

    function & GetPage()
    {
        static $_page;
        if( empty($_page) )
            $_page = new CCPage();
        return($_page);
    }

    function ViewFile($template)
    {
        if( empty($this) || (get_class($this) != 'CCPage') )
            $this =& CCPage::GetPage();
        
        $this->_body_template = $template;
    }

    function PageArg($name, $value, $macroname='')
    {
        CCPage::AddTemplateVars($name,$value,$macroname);
    }

    function SetTitle( $title )
    {
        CCPage::PageArg('page-title',$title);
    }

    function PrintPage( & $body )
    {
        CCPage::AddPrompt('body_text',$body);
        CCPage::Show();
    }

    function SetStyleSheet( $css, $title = 'Style Sheet' )
    {
        CCPage::AddLink( 'head_links', 'stylesheet', 'text/css', $css, $title );
    }

    function AddRssFeed( $feed_url, $link_text, $title = 'RSS Feed' )
    {
        CCPage::AddLink( 'head_links', 'alternate', 'application/rss+xml', $feed_url, $title );
        CCPage::AddLink( 'foot_links', 'alternate', 'application/rss+xml', $feed_url, $title, $link_text );
    }

    function Show()
    {
        global $CC_GLOBALS;

        /////////////////
        // Step -2
        //
        // Don't do this method from command line
        //
        if( !CCUtil::IsHTTP() )
            return;

        /////////////////
        // Step -1
        //
        // Allow static call
        //
        if( empty($this) || (get_class($this) != 'CCPage') )
            $this =& CCPage::GetPage();

        /////////////////
        // Step 1
        //
        // Merge config into already existing page args
        //
        $this->_page_args = array_merge($this->_page_args,$CC_GLOBALS); // is this right?

        /////////////////
        // Step 1a
        //
        // Trigger custom macros
        //
        // 
        $configs =& CCConfigs::GetTable();
        $tmacs = $configs->GetConfig('tmacs');
        foreach( $tmacs as $K => $V )
        {
            if( $V )
                $this->_page_args['custom_macros'][] = "custom.xml/$K";
        }

        /////////////////
        // Step 2
        //
        // Pick style....
        //
        $style_set = false;
        if( CCUser::IsLoggedIn() )
        {
            $cookiename = 'style-sheet-' . CCUser::CurrentUserName();
            if( !empty($_COOKIE[$cookiename]) )
            {
                $this->SetStyleSheet($_COOKIE[$cookiename],"User Styles");
                $style_set = true;
            }
        }

        if( !$style_set )
        {
            $settings = $configs->GetConfig('settings');
            $this->SetStyleSheet($settings['style-sheet'],'Default Style');
        }

        $isadmin = CCUser::IsAdmin();

        /////////////////
        // Step 3
        //
        // Populate current user's name
        //
        if( CCUser::IsLoggedIn() )
            $this->_page_args['logged_in_as'] = CCUser::CurrentUserName();
        $this->_page_args['is_admin']  = $isadmin;
        $this->_page_args['not_admin'] = !$isadmin;

        /////////////////
        // Step 4
        //
        // Populate menu
        //
        $this->_page_args['menu_groups'] =& CCMenu::GetMenu();

        /////////////////
        // Step 5
        //
        // Populate a custom body template
        //
        // 
        if( !empty($this->_body_template) )
        {
            global $CC_GLOBALS;
            $template = new CCTemplate( $CC_GLOBALS['files-root'] . $this->_body_template,true);
            $body =& $template->SetAllAndParse($this->_page_args, false, $isadmin);
            $this->AddPrompt('body_text',$body);
        }

        //CCDebug::LogVar("pageargs",$this->_page_args);

        $this->SetAllAndPrint($this->_page_args, CCUser::IsAdmin() );
    }

    function PhpError($err_msg)
    {
        CCPage::AddPrompt('php_error_message',$err_msg);
    }

    function SystemError($err_msg)
    {
        if( !CCUtil::IsHTTP() )
        {
            print($err_msg);
        }
        else
        {
            CCPage::AddPrompt('system_error_message',$err_msg);
        }
    }

    function Prompt($prompt_text)
    {
        CCPage::AddPrompt('system_prompt',$prompt_text);
    }
    
    function AddForm($form)
    {
        $vars = $form->GetTemplateVars();
        CCPage::AddTemplateVars( $vars, null, $form->GetTemplateMacro() );
    }
 
    function AddTemplateVars(&$vars, $value, $macroname='')
    {
        if( empty($this) || (get_class($this) != 'CCPage') )
            $this =& CCPage::GetPage();

        if( is_array($vars) )
            $this->_page_args = array_merge($this->_page_args,$vars);
        else
            $this->_page_args[$vars] = $value;

        if( !empty($macroname) )
            $this->_page_args['macro_names'][] = $macroname;
    }

    function AddLink($placement, $rel, $type, $href, $title, $link_text = '')
    {
        if( empty($this) || (get_class($this) != 'CCPage') )
            $this =& CCPage::GetPage();

        $this->_page_args[$placement][] = array(   'rel'       => $rel,
                                                   'type'      => $type,
                                                   'href'      => $href,
                                                   'title'     => $title,
                                                   'link_text' => $link_text);
    }


    function AddPrompt($name,$value)
    {
        if( empty($this) || (get_class($this) != 'CCPage') )
            $this =& CCPage::GetPage();

        $this->_page_args['prompts'][] = array(  'name' => $name,
                                                 'value' => $value );

        if( empty($this->_page_args['macro_names']) || !in_array( 'prompts', $this->_page_args['macro_names'] ) )
            $this->_page_args['macro_names'][] = 'prompts';
    }
}


?>