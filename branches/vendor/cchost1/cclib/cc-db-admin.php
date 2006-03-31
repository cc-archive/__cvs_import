<?
// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU,           array( 'CCDatabaseAdmin', 'OnBuildMenu') );
CCEvents::AddHandler(CC_EVENT_MAP_URLS,            array( 'CCDatabaseAdmin', 'OnMapUrls') );

$CC_SQL_DATE = '%W, %M %e, %Y @ %l:%i %p';

/**
* Configuration form for database admin
*
**/
class CCAdminDatabaseForm extends CCForm
{
    function CCAdminDatabaseForm()
    {
        $this->CCForm();
        $fields = array( 
                    'db-name'        => 
                       array( 'label'       => 'mysql Database Name',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

                    'db-server'        => 
                       array( 'label'       => 'Location of Server ',
                               'form_tip'   => 'Typically "localhost"',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

                    'db-user'        => 
                       array( 'label'       => 'mysql User Name',
                               'form_tip'   => 'This is the name used to connect to the mysql database.',
                               'formatter'  => 'textedit',
                               'flags'      => CCFF_POPULATE | CCFF_REQUIRED ),

                    'db-password'    => 
                       array( 'label'       => 'mysql User Password',
                               'form_tip'   => 'This is the password used to connect to the mysql database.',
                               'nomd5'      => true,
                               'formatter'  => 'password',
                               'flags'      => CCFF_POPULATE  ),
                       );

        $this->AddFormFields($fields);
    }
}

/**
* Database Admin Callbacks
*
*/
class CCDatabaseAdmin
{
    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array( 
            'adminmysql'   => array( 'menu_text'  => 'Database',
                             'menu_group' => 'configure',
                             'access' => CC_ADMIN_ONLY,
                             'weight' => 10000,
                             'action' =>  ccl('admin','database')
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
        CCEvents::MapUrl( 'admin/database',  array('CCDatabaseAdmin','Admin'), CC_ADMIN_ONLY );
    }

    /**
    * Handler for /admin/database
    *
    * Wildly dangerous, guaranteed to generate support calls.
    *.
    * @see CCAdminDatabaseForm::CCAdminDatabaseForm
    */
    function Admin()
    {
        CCPage::SetTitle('Database Configuration');
        $form = new CCAdminDatabaseForm();
        $config_db = CCDatabase::_config_db();
        if( empty($_POST['admindatabase']) || !$form->ValidateFields() )
        {   
            include($config_db);
            $form->PopulateValues($CC_DB_CONFIG);
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->GetFormValues($CC_DB_CONFIG);

            $varname = "\$CC_DB_CONFIG";
            $text = "<?";
            $text .= <<<END
        
// This file is generated as part of install and config editing

if( !defined('IN_CC_HOST') )
    die('Welcome to CC Host');

$varname = array (
   'db-name'     =>   '{$CC_DB_CONFIG['db-name']}',
   'db-server'   =>   '{$CC_DB_CONFIG['db-server']}',
   'db-user'     =>   '{$CC_DB_CONFIG['db-user']}',
   'db-password' =>   '{$CC_DB_CONFIG['db-password']}',
 
  ); 

END;

            $text .= "?>";

            $f = fopen($config_db, 'w+' );
            fwrite($f,$text,strlen($text));
            fclose($f);

            CCPage::Prompt("Database configuration saved.");
        }

    }

}

?>