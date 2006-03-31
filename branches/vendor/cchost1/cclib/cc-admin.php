<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,          array( 'CCAdmin' , 'OnBuildMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,           array( 'CCAdmin' , 'OnMapUrls') );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCAdmin' , 'OnGetConfigFields') );

/**
 * Derive from this class to let the user modify the app's config 
 *
 * There are many derivations of this, one for each group of config variables.
 * When you derive from this form, it will save the values here into the config
 * table for use in all subsequent sessions. The derivations
 * do not have to perform any action on user submit. The global config affects
 * all users so typically only administrators will see derivations of this form.
 *  
 *  
 *  For example:
 *
 * <code>
 *
    // Derive from the base 
    class CCMyAdminForm extends CCEditConfigForm
    {
        function CCMyAdminForm()
        {
            $this->CCEditConfigForm('my-settings-type'); // name of the type
            $fields = array( 
                        'mySettting' =>  // name of the setting
                           array(  'label'      => 'Set this setting',
                                   'form_tip'   => 'make it good',
                                   'value'      => 'Admin',
                                   'formatter'  => 'textedit',
                                   'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),
                           );

            $this->AddFormFields($fields);
       }
    }


    // Then later in the code when it's time to call it up, simply do:
    function ShowMyAdminForm()
    {
        CCPage::SetTitle('My Admin Form');
        $form = new CCMyAdminForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    // Still later you can retreive the user's setting:
    function DoMyStuff()
    {
        $configs =& CCConfigs::GetTable();
        $settings = $configs->GetConfig('my-settings-type');

        $value = $settings['mySetting'];

        ///...
    }
    
 * 
 *  </code>
 *
 *
 */
class CCEditConfigForm extends CCForm
{
    var $_typename;

    /**
     * Constructor
     *
     */
    function CCEditConfigForm($config_type)
    {
        $this->CCForm();
        $this->SetSubmitText('Submit');
        $this->SetHandler( ccl('admin', 'save') );
        $this->SetHiddenField( '_name', get_class($this), CCFF_HIDDEN | CCFF_NOUPDATE );
        $this->SetConfigType($config_type);
    }

    /**
     * Overrides base class in order to populate fields with current contents of environment's config.
     *
     */
    function GenerateForm()
    {
        $configs =& CCConfigs::GetTable();
        $values = $configs->GetConfig($this->_typename);
        $this->PopulateValues($values);
        return( parent::GenerateForm() );
    }

    /**
    * Sets the config tye for the data (also done from ctor)
    * 
    */
    function SetConfigType($typename)
    {
        $this->_typename = $typename;
    }

    /**
    * Saves this forms data to the proper type of the current scope
    * 
    * @see CCConfigs::SaveConfig
    */
    function SaveToConfig()
    {
        $configs =& CCConfigs::GetTable();
        $this->GetFormValues($values);
        $configs->SaveConfig($this->_typename, $values);
    }
}

/**
* Displays global configuration options.
*
*/
class CCAdminConfigForm extends CCEditConfigForm
{
    function CCAdminConfigForm()
    {
        $this->CCEditConfigForm('config');
        $fields = array();
        CCEvents::Invoke( CC_EVENT_GET_CONFIG_FIELDS, array( CC_GLOBAL_SCOPE, &$fields ) );
        $this->AddFormFields($fields);
   }

}

/**
* Displays local configuration options.
*
*/
class CCAdminSettingsForm extends CCEditConfigForm
{
    function CCAdminSettingsForm()
    {
        $this->CCEditConfigForm('settings');
        $fields = array();
        CCEvents::Invoke( CC_EVENT_GET_CONFIG_FIELDS, array( CC_LOCAL_SCOPE, &$fields ) );
        $this->AddFormFields($fields);
   }

}

/**
* This form edits the raw configation data
*
* This is not on any menu, admins can reach it via /main/admin/edit
*
*/
class CCAdminRawForm extends CCGridForm
{
    /**
    * Constructor
    *
    **/
    function CCAdminRawForm()
    {
        $this->CCGridForm();

        $heads = array( "Setting", "Value" );
        $this->SetColumnHeader($heads);

        $configs =& CCConfigs::GetTable();
        $configs->SetSort('config_scope,config_type');
        $rows = $configs->QueryRows('');

        foreach( $rows as $row )
        {
            if( $row['config_type'] == 'menu' || $row['config_type'] == 'urlmap')
                continue;

            $id   = $row['config_id'];
            $arr  = unserialize( $row['config_data'] );
            $c    = count($arr);
            $keys = array_keys($arr);
            
            for( $i = 0; $i < $c; $i++ )
            {
                $name = $keys[$i];
                $a = $this->_make_field($row,$id,$i,$name,$arr[$name]);
                $uid = $name . '_' . $id . '_' . $i;
                $this->AddGridRow( $uid, $a );
            }
        }

        $this->SetSubmitText('Save Configuration');
        $this->SetHelpText("Just be careful what you do here, it's easy to 'break the site'");
    }

    /**
    * Local helper
    *
    */
    function _make_field($row,$id,$i,$name,$value)
    {
        if( strchr($value,"\n") )
        {
            $formatter = 'textarea';
        }
        else
        {
            $formatter = 'textedit';
        }
        $tname = $row['config_scope'] . '::' . $row['config_type'] . '[' . $name . ']';
        $a = array(
                  array(
                    'element_name'  => 'cfg_' . $id . '_' . $i,
                    'value'      => $tname,
                    'formatter'  => 'statictext',
                    'flags'      => CCFF_STATIC ),
                  array(
                    'element_name'  => "cfg[$id][$name]",
                    'value'      => htmlspecialchars($value),
                    'formatter'  => $formatter,
                    'flags'      => CCFF_NONE ),
                );

        return($a);
    }
}


/**
* Basic admin API and system event watcher.
* 
*/
class CCAdmin
{
    /**
    * This form edits the raw configation data
    *
    * This is not on any menu, admins can reach it via /main/admin/edit
    *
    * @see CCAdminRawForm::CCAdminRawForm
    */
    function Deep()
    {
        CCPage::SetTitle("Edit Raw Configuation Data");
        if( empty($_POST['adminraw']) )
        {
            $form = new CCAdminRawForm();
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $cfgs = $_POST['cfg'];
            $configs =& CCConfigs::GetTable();
            foreach( $cfgs as $id => $data )
            {
                CCUtil::StripSlash($data);
                $where['config_id'] = $id;
                $where['config_data'] = serialize($data);
                //CCDebug::LogVar($id,$data);
                $configs->Update($where);
            }

            CCPage::Prompt("Configuration Changes Saved");
        }
    }

    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings settings to config editing form
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields['cookie-domain'] =
               array( 'label'       => 'Cookie Domain',
                       'form_tip'      => 'This is the name used to set cookies on the client machine.',
                       'value'      => '',
                       'formatter'  => 'textedit',
                       'flags'      => CCFF_POPULATE | CCFF_REQUIRED );

            $fields['pretty-urls'] = 
               array( 'label'       => 'Use URL ReWrite Rules',
                       'form_tip' 
                               => 'Check this if you want to use mod_rewrite for \'pretty\' URLs.',
                       'value'      => 0,
                       'formatter'  => 'checkbox',
                       'flags'      => CCFF_HIDDEN);
        }
    }

    /**
    * Event handler for menu building
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'adminadvanced'   => array( 'menu_text'  => 'Global Setup',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 10000,
                             'action' =>  ccl('admin','setup')
                             ),
            'settings'   => array( 'menu_text'  => 'Settings',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 1,
                             'action' =>  ccl('admin','settings')
                             ),
            'virtualhost'   => array( 'menu_text'  => 'Virtual CC Host',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 10001,
                             'action' =>  ccl('viewfile','howtovirtual.xml')
                             ),
            'adminhelp'   => array( 'menu_text'  => 'Admin Help',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 10002,
                             'action' =>  ccl('viewfile','adminhelp.xml')
                             ),
                );

        CCMenu::AddItems($items);

        $groups = array(
                    'configure' => array( 'group_name' => 'Configure Site',
                                          'weight'    => 100 ),
                    );

        CCMenu::AddGroups($groups);
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/save',     array('CCAdmin', 'SaveConfig'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/setup',    array('CCAdmin', 'Setup'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/settings', array('CCAdmin', 'Settings'),   CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/edit',     array('CCAdmin', 'Deep'),       CC_ADMIN_ONLY );
    }

    /**
    * Handler for /admin/setup
    *
    * @see CCAdminConfigForm::CCAdminConfigForm
    */
    function Setup()
    {
        CCPage::SetTitle('Global Site Setup');
        $form = new CCAdminConfigForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Handler for /admin/settings
    *
    * @see CCAdminConfigForm::CCAdminConfigForm
    */
    function Settings()
    {
        CCPage::SetTitle('Site Settings');
        $form = new CCAdminSettingsForm();
        CCPage::AddForm( $form->GenerateForm() );
    }

    /**
    * Method called when the user submits a config editing form.
    *
    * On rare occasions you may want to do special processing on user 
    * submit of an admin/config. At some point you call this to
    * save the new config values. 
    * @see CCEditConfigForm::CCEditConfigForm
    */
    function SaveConfig($form = '')
    {
        if( empty($form) )
        {
            $form_name = CCUtil::StripText($_REQUEST['_name']);
            $form = new $form_name();
        }

        if( $form->ValidateFields() )
        {
            $form->SaveToConfig();
        }
        else
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
    }

}

?>