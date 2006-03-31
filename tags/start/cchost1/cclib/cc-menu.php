<?

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,    array( 'CCMenu', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,     array( 'CCMenu', 'OnMapUrls'));

class CCAdminMenuForm extends CCGridForm
{
    function CCAdminMenuForm($menu,$groups)
    {
        $this->CCGridForm();

        uasort($menu,'cc_sort_user_menu');
        uasort($groups,'cc_weight_sorter');

        $heads = array( "Menu Text", "Group", "Weight", "Access" );
        $this->SetColumnHeader($heads);

        $group_select = array();
        foreach( $groups as $groupname => $groupinfo )
            $group_select[$groupname] = $groupinfo['group_name'];

        foreach( $menu as $keyname => $menuitem )
        {
            $a = array(
                  array(
                    'element_name'  => "mi[$keyname][menu_text]",
                    'value'      => $menuitem['menu_text'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][menu_group]",
                    'value'      => $menuitem['menu_group'],
                    'formatter'  => 'select',
                    'options'    => &$group_select,
                    'flags'      => CCFF_NONE ),
                  array(
                    'element_name'  => "mi[$keyname][weight]",
                    'value'      => $menuitem['weight'],
                    'formatter'  => 'textedit',
                    'class'      => 'cc_form_input_short',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][access]",
                    'value'      => $menuitem['access'],
                    'formatter'  => 'select',
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                           CC_ONLY_NOT_LOGGED_IN  => 'Anonymous users only',
                                           CC_DONT_CARE_LOGGED_IN => "Everyone",
                                           CC_ADMIN_ONLY          => "Administrators only" ),
                    'flags'      => CCFF_NONE ),
                );

            $this->AddGridRow( $keyname, $a );
            if( !empty($menuitem['menu_flags']) )
            {
                $this->SetHiddenField( "mi[$keyname][menu_flags]", $menuitem['menu_flags'] );
                if( $menuitem['menu_flags'] & CCMF_CUSTOM )
                    $this->SetHiddenField( "mi[$keyname][action]", 
                                htmlspecialchars(urlencode($menuitem['action'])) );
            }
            $this->SetSubmitText('Submit Menu Changes');
        }
    }
}

class CCAdminMenuGroupsForm extends CCGridForm
{
    function CCAdminMenuGroupsForm($groups)
    {
        $this->CCGridForm();

        $heads = array( "Group Name", "Weight" );
        $this->SetColumnHeader($heads);

        foreach( $groups as $keyname => $group )
        {
            $a = array(
                  array(
                    'element_name'  => "grp[$keyname][group_name]",
                    'value'      => $group['group_name'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "grp[$keyname][weight]",
                    'value'      => $group['weight'],
                    'formatter'  => 'textedit',
                    'class'      => 'cc_form_input_short',
                    'flags'      => CCFF_REQUIRED ),
                );

            $this->AddGridRow( $keyname, $a );
            $this->SetSubmitText('Submit Group Changes');
        }
    }
}

class CCEditLinksForm extends CCGridForm
{
    function CCEditLinksForm($menu)
    {
        $this->CCGridForm();

        $heads = array( "Delete", "Menu Text", "Action", "Weight", "Access" );
        $heads_out = false;

        foreach( $menu as $keyname => $menuitem )
        {
            if( empty($menuitem['menu_flags']) )
                continue;

            if( !($menuitem['menu_flags'] & CCMF_CUSTOM) )
                continue;

            $a = array(
                  array(
                    'element_name'  => "mi[$keyname][delete]",
                    'formatter'  => 'checkbox',
                    'flags'      => CCFF_NONE ),
                  array(
                    'element_name'  => "mi[$keyname][menu_text]",
                    'class'      => 'cc_form_input_short',
                    'value'      => $menuitem['menu_text'],
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][action]",
                    'value'      => htmlspecialchars($menuitem['action']),
                    'formatter'  => 'textedit',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][weight]",
                    'value'      => $menuitem['weight'],
                    'formatter'  => 'textedit',
                    'class'      => 'cc_form_input_short',
                    'flags'      => CCFF_REQUIRED ),
                  array(
                    'element_name'  => "mi[$keyname][access]",
                    'value'      => $menuitem['access'],
                    'formatter'  => 'select',
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                           CC_ONLY_NOT_LOGGED_IN  => 'Anonymous users only',
                                           CC_DONT_CARE_LOGGED_IN => "Everyone",
                                           CC_ADMIN_ONLY          => "Administrators only" ),
                    'flags'      => CCFF_NONE ),
                );

            if( !$heads_out )
            {
                $this->SetColumnHeader($heads);
                $heads_out = true;
            }

            $this->AddGridRow( $keyname, $a );
        }

        if( $heads_out )
        {
            $this->SetSubmitText('Submit Menu Changes');
        }
        else
        {
            $this->SetHelpText('There are no links to edit yet, use the Add Links menu item');
            $this->SetSubmitText('');
        }
    }
}

class CCAddLinkForm extends CCForm
{
    function CCAddLinkForm()
    {
        $this->CCForm();

        $fields = array(
            'menu_text' => array(
                'label'  => 'Text',
                 'flags'     => CCFF_REQUIRED,
                 'formatter' => 'textedit' ),
            'action'     => array(
                 'label'  => 'URL',
                 'flags'      => CCFF_REQUIRED,
                  'formatter' => 'textedit' ),
            'weight'     => array(
                 'label'  => 'Weight',
                 'flags'      => CCFF_REQUIRED,
                    'formatter'  => 'textedit',
                    'class'      => 'cc_form_input_short' ),
             'access' => array(
                  'label' => 'Permissions',
                   'flags'    => CCFF_NONE,
                    'value'      => CC_DONT_CARE_LOGGED_IN,
                    'formatter'  => 'select',
                    'options'    => array( CC_MUST_BE_LOGGED_IN   => 'Logged in users only',
                                           CC_ONLY_NOT_LOGGED_IN  => 'Anonymous users only',
                                           CC_DONT_CARE_LOGGED_IN => "Everyone",
                                           CC_ADMIN_ONLY          => "Administrators only" ) ),
                     );

        $this->AddFormFields($fields);
        $this->SetHiddenField('menu_group','links');
        $this->SetHiddenField('menu_flags', CCMF_CUSTOM );
                    
    }
}

class CCMenu
{
    function GetMenu($force = false)
    {
        static $_menu;
        if( $force || !isset($_menu) )
            $_menu = CCMenu::_build_menu();
        return( $_menu );
    }

    function GetLocalMenu($menuname,$args=array())
    {
        // Invoke the event....
        
        $allmenuitems = array();
        $c = count($args);
        $r = array( &$allmenuitems );
        for( $i = 0; $i < $c; $i++ )
            $r[] =& $args[$i];

        CCEvents::Invoke($menuname, $r );

        // sort the results
        
        usort($allmenuitems ,'cc_weight_sorter');

        // filter the results based on access permissions

        $mask = CCMenu::GetAccessMask();

        $menu = array();
        $count = count($allmenuitems);
        $keys = array_keys($allmenuitems);
        for( $i = 0; $i < $count; $i++ )
        {
            $key = $keys[$i];
            if( ($allmenuitems[$key]['access'] & $mask) != 0 )
                $menu[] = $allmenuitems[$key];
        }

        //CCDebug::PrintVar($menu);

        return( $menu );
    }

    // Occasionally the menu needs to be reset (e.g. a user logs out)
    function Reset()
    {
        CCMenu::_menu_data(true);
        CCMenu::GetMenu(true);
    }

    function AddItems($items)
    {
        $menu_items =& CCMenu::_menu_items();
        $menu_items = array_merge($menu_items,$items);
    }

    function AddGroups($groupstuff)
    {
        $groups =& CCMenu::_menu_groups();
        $groups = array_merge($groups,$groupstuff);
    }

    function GetAccessMask()
    {
        if( CCUser::IsLoggedIn() )
        {
            $mask = CC_MUST_BE_LOGGED_IN | CC_DONT_CARE_LOGGED_IN;

            if( CCUser::IsAdmin() )
                $mask |= CC_ADMIN_ONLY;
        }
        else
        {
            $mask = CC_ONLY_NOT_LOGGED_IN | CC_DONT_CARE_LOGGED_IN;
        }
        return( $mask );
    }

    function _build_menu()
    {
        $mask        =  CCMenu::GetAccessMask();
        $groups      =  CCMenu::_menu_groups();
        $menu_items  =& CCMenu::_menu_items(); 

        foreach( $menu_items as $name => $item )
        {
            if( ($item['access'] & $mask) != 0 )
                $groups[$item['menu_group']]['menu_items'][] = $item;
        }

        $menu = array();
        foreach( $groups as $groupname => $group  )
        {
            if( !empty($group['menu_items']) )
            {
                usort( $group['menu_items'], 'cc_weight_sorter' );
                $group['group_id'] = $groupname . "_group";
                $menu[] = $group;
            }
        }

        return( $menu );
    }

    function &_menu_data($force = false, $action = CC_MENU_DISPLAY )
    {
        static $_menu_data;
        if( $force || !isset($_menu_data) )
        {
            $_menu_data = array( 'items' => array(),
                                 'groups' => array() );

            //
            // ::::: Weirdass side effect warning :::::
            //
            // event handlers responding to this event will
            // fill the _menu_data var through calls to
            // CCMenu::AddMenuItem()
            //
            CCEvents::Invoke(CC_EVENT_MAIN_MENU, array( $action ));

            $configs =& CCConfigs::GetTable();
            $custom_data = $configs->GetConfig('menu');
            if( !empty($custom_data) )
            {
                $menu =& $_menu_data['items'];
                foreach( $custom_data as $K => $V )
                {
                    if( !empty($V['menu_flags']) && ($V['menu_flags'] & CCMF_CUSTOM) )
                    {
                        $menu[$K] = $V;
                    }
                    else
                    {
                        $menu[$K] = array_merge($menu[$K],$V);
                    }
                }
            }

            $groups_data = $configs->GetConfig('groups');
            if( !empty($groups_data) )
            {
                $_menu_data['groups'] = $groups_data;
            }
            uasort($_menu_data['groups'],'cc_weight_sorter');

        }

        return( $_menu_data );
    }

    function & _menu_items()
    {
        $data =& CCMenu::_menu_data();
        return($data['items']);
    }

    function & _menu_groups()
    {
        $data =& CCMenu::_menu_data();
        return($data['groups']);
    }

    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'adminmenu'   => array( 'menu_text'  => 'Menus',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 60,
                             'action' =>  ccl('admin','menu')
                             ),
            'adminmenugrp' => array( 'menu_text'  => 'Menu Groups',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 60,
                             'action' =>  ccl('admin','menugroup')
                             ),

            'home' => array( 'menu_text'  => 'Home',
                             'menu_group' => 'links',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 1,
                             'action' =>  ccd( '' )
                             ),
            'addlink' => array( 'menu_text'  => 'Add Link',
                             'menu_group' => 'links',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 1000,
                             'action' =>  ccl( 'admin','addlink' )
                             ),
            'editlinks' => array( 'menu_text'  => 'Edit Links',
                             'menu_group' => 'links',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 1001,
                             'action' =>  ccl( 'admin','editlinks' )
                             ),
                );

        CCMenu::AddItems($items);

        $groups = array(
                    'links' => array( 'group_name' => 'Links',
                                      'weight'    => 3 ),
                    );

        CCMenu::AddGroups($groups);

    }

    function EditLinks()
    {
        CCPage::SetTitle("Edit Custom Link Items");
        CCMenu::_menu_data(true,CC_MENU_EDIT);
        $menu_items  =& CCMenu::_menu_items(); 
        $form = new CCEditLinksForm($menu_items);
        if( empty($_POST['editlinks']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $menu_items = $_POST['mi'];
            $copy = array();
            $has_delete = false;
            foreach( $menu_items as $name => $edits )
            {
                if( array_key_exists('delete',$menu_items[$name]) )
                {
                    $has_delete = true;
                    continue;
                }

                $copy[$name]['menu_text']  = CCUtil::StripText($edits['menu_text']);
                $copy[$name]['menu_group'] = 'links';
                $copy[$name]['weight']     = CCUtil::StripText($edits['weight']);
                $copy[$name]['access']     = CCUtil::StripText($edits['access']) ;
                $copy[$name]['menu_flags'] = CCMF_CUSTOM; // todo: other flags??
                $copy[$name]['action']     = htmlspecialchars(urldecode($edits['action'])) ;
            }

            if( $has_delete )
            {
                $configs =& CCConfigs::GetTable();
                $org_menu = $configs->GetConfig('menu');
                foreach( $org_menu as $K => $V )
                {
                    if( !empty($V['menu_flags']) && ($V['menu_flags'] & CCMF_CUSTOM) )
                        continue;

                    $copy[$K] = $V;
                }
            }

            $this->_save_out_menu($copy,false);

            CCPage::Prompt("Menu changes have been saved");

            $this->Reset();
        }
    }

    function AddLink()
    { 
        CCPage::SetTitle("Add Menu Link Item");
        $form = new CCAddLinkForm();
        if( empty($_POST['addlink']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $menu_items = $this->_get_clean_menu();

            // find a unique name for the new item
            $i = 0;
            while( array_key_exists( 'link' . $i, $menu_items ) )
                $i++;

            $form->GetFormValues($newitem);
            $menu_items['link' . $i] = $newitem;
            $this->_save_out_menu($menu_items);

            // display it right now...
            $real_menu =& $this->_menu_items(); // this is real
            $real_menu['link' . $i] = $newitem;
        }
    }

    function _get_clean_menu()
    {
        // we don't want to save the 'action' field to
        // disk if we don't have to (many of these need
        // to be dynamically filled in anyway at runtime)

        $menu_items = $this->_menu_items(); // this is a copy
        $c = count($menu_items);
        $keys = array_keys($menu_items);
        for( $i = 0; $i < $c; $i++ )
        {
            $k = $keys[$i];
            if( !array_key_exists('menu_flags',$menu_items[$k]) ||
                    !($menu_items[$k]['menu_flags'] & CCMF_CUSTOM) )
            {
                unset($menu_items[$k]['action']);
            }
        }
        return( $menu_items );
    }

    /**
    * Event handler for mapping urls to methods
    *
    * @see CCEvents::MapUrl
    */
    function OnMapUrls()
    {
        CCEvents::MapUrl( 'admin/menu',       array('CCMenu', 'Admin'),      CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/menugroup',  array('CCMenu', 'AdminGroup'), CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/addlink',    array('CCMenu', 'AddLink'),    CC_ADMIN_ONLY );
        CCEvents::MapUrl( 'admin/editlinks',  array('CCMenu', 'EditLinks'),  CC_ADMIN_ONLY );
    }

    function Admin()
    {
        CCPage::SetTitle("Edit Menus");

        if( empty($_POST['adminmenu']) )
        {
            CCMenu::_menu_data(true,CC_MENU_EDIT);
            $groups      =  CCMenu::_menu_groups();
            $menu_items  =& CCMenu::_menu_items(); 
            $form = new CCAdminMenuForm($menu_items,$groups);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $menu_items = $_POST['mi'];
            $copy = array();
            foreach( $menu_items as $name => $edits )
            {
                $copy[$name]['menu_text']  = CCUtil::StripText($edits['menu_text']);
                $copy[$name]['menu_group'] = CCUtil::StripText($edits['menu_group']);
                $copy[$name]['weight']     = CCUtil::StripText($edits['weight']);
                $copy[$name]['access']     = CCUtil::StripText($edits['access']) ;
                if( !empty($edits['menu_flags']) )
                  $copy[$name]['menu_flags'] = CCUtil::StripText($edits['menu_flags']) ;
                if( !empty($edits['action']) )
                  $copy[$name]['action'] = htmlspecialchars(urldecode($edits['action'])) ;
            }

            $this->_save_out_menu($copy);

            CCPage::Prompt("Menu changes have been saved");
        
            $this->Reset();
        }            

    }

    function _save_out_menu($menu,$merge=true)
    {
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('menu',$menu,'',$merge);
    }

    function _save_out_groups($groups)
    {
        $configs =& CCConfigs::GetTable();
        $configs->SaveConfig('groups',$groups,'',false);
    }

    function AdminGroup()
    {
        CCPage::SetTitle("Edit Menu Groups");

        if( empty($_POST['adminmenugroups']) )
        {
            CCMenu::_menu_data(true,CC_MENU_EDIT);
            $groups      =  CCMenu::_menu_groups();
            $form = new CCAdminMenuGroupsForm($groups);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $groups = $_POST['grp'];
            array_walk($groups,'cc_strip_groups');
            $this->_save_out_groups($groups);
            CCPage::Prompt("Menu group changes have been saved");
        }            

        $this->Reset();

    }

}

function cc_weight_sorter($a, $b)
{
   return( $a['weight'] > $b['weight'] ? 1 : -1 );
}

function cc_sort_user_menu($a, $b)
{
    if( $a['menu_group'] == $b['menu_group'] )
        return( cc_weight_sorter($a,$b) );
    return( cc_weight_sorter($a['menu_group'],$b['menu_group']) );
}

function cc_strip_groups(&$i,$k)
{
    $i['group_name']   = CCUtil::StripText($i['group_name']);
    $i['weight'] = CCUtil::StripText($i['weight']);
}
?>