<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_MAIN_MENU, array( 'CCLogin', 'OnBuildMenu'));
CCEvents::AddHandler(CC_EVENT_APP_INIT,  array( 'CCLogin', 'InitCurrentUser'));
CCEvents::AddHandler(CC_EVENT_MAP_URLS,  array( 'CCLogin', 'OnMapUrls'));

/**
* Wrapper for cc_tbl_keys database table, used in register spam prevention
*/
class CCSecurityKeys extends CCTable
{
    /**
    * Constructor (use GetTable() to get an instance of this table)
    *
    * @see GetTable
    */
    function CCSecurityKeys()
    {
        $this->CCTable('cc_tbl_keys','keys_id');
    }

    /**
    * Returns static singleton of configs table wrapper.
    * 
    * Use this method instead of the constructor to get
    * an instance of this class.
    * 
    * @returns object $table An instance of this table
    */
    function & GetTable()
    {
        static $_table;
        if( empty($_table) )
            $_table = new CCSecurityKeys();
        return( $_table );
    }

    /**
    * Add a key record to the database and returns a key that should match later
    *
    * @returns integer $id ID of this key
    */
    function AddKey($key)
    {
        $this->CleanUp();
        $ip = $_SERVER["REMOTE_ADDR"];
        $dbargs['keys_key']  = $key;
        $dbargs['keys_ip']   = $ip;
        $dbargs['keys_time'] = date('Y-m-d H:i');
        $this->Insert($dbargs);
        $id = $this->QueryKey("keys_key = '$key' AND keys_ip = '$ip'");
        return($id);
    }

    /**
    * Clean up utility function, empties the database of record over an hour old.
    */
    function CleanUp()
    {
        $this->DeleteWhere('keys_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    }

    /** 
    * Verify a key/id pair are a match
    */
    function IsMatch($key,$id)
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $real_id = $this->QueryKey("keys_key = '$key' AND keys_ip = '$ip'");
        return( $real_id === $id );
    }

    /**
    * Generate a fairly unique, kinda sorta unpredicatble key
    */
    function GenKey()
    {
        $raw = $_SERVER["REMOTE_ADDR"] . ' ' . microtime();
        $hash = md5($raw);
        return( substr($hash,intval($hash[0],16),5) );
    }

}

/**
* Registeration form
*/
class CCNewUserForm extends CCUserForm
{
    /**
    * Constructor
    */
    function CCNewUserForm()
    {
        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => 'Login Name',
                               'formatter'  => 'newusername',
                               'form_tip'   => 'Must be letter, numbers or underscore (_), no longer than 25 characters',
                               'flags'      => CCFF_REQUIRED  ),

                    'user_email' =>
                       array( 'label'       => 'e-mail',
                               'formatter'  => 'email',
                               'form_tip'   => 'This address will never show on the site but is '.
                                                'required for creating a new account and password '.
                                                'recovery in case you forget it.',
                               'flags'      => CCFF_REQUIRED ),

                    'user_password' =>
                       array( 'label'       => 'Password',
                               'formatter'  => 'password',
                               'form_tip'   => 'Must be at least 5 characters',
                               'flags'      => CCFF_REQUIRED ),

                    'user_mask' =>
                       array( 'label'       => '',
                               'formatter'  => 'securitykey',
                               'form_tip'   => '',
                               'flags'      => CCFF_NOUPDATE),
                    'user_confirm' =>
                       array( 'label'       => 'Security Key',
                               'formatter'  => 'password',
                               'form_tip'   => 'Type in characters above',
                               'flags'      => CCFF_REQUIRED | CCFF_NOUPDATE),
                    '_lost_password' =>
                       array( 'label'       => 'Lost Password?',
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . ccl('lostpassword') . '">Click Here</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                        );

        $this->AddFormFields( $fields );
        $this->SetSubmitText('Submit');
    }

    /**
     * Handles generation of &lt;img's and a hidden $id field
     *
     * The img tags are actually stuff with '/s/#' URLs that call back to
     * this module and return a bitmap corresponding to the security key's
     * id. The '#' is combination of id the index into the key in question
     * 
     * @CCLogin::OnSecurityCallback
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_securitykey($varname,$value='',$class='')
    {
        $keys =& CCSecurityKeys::GetTable();
        $hash = $keys->GenKey();
        $id = $keys->AddKey($hash);
        $len = strlen($hash);
        $html = "<table><tr><td>";
        for( $i = 0; $i < $len; $i++ )
        {
            $url = ccl('s', ($id * 100) + $i);
            $html .= "<img src=\"$url\" />";
        }
        $html .= "</td></tr></table><input type=\"hidden\" name=\"$varname\" id=\"$varname\" value=\"$id\" />";
        return($html);
    }

    function validator_securitykey($fieldname)
    {
        $id = $this->GetFormValue('user_mask');
        $hash = CCUtil::StripText($_POST['user_confirm']);
        $keys =& CCSecurityKeys::GetTable();
        $retval = $keys->IsMatch( $hash, $id );
        if( !$retval )
        {
            $this->SetFieldError($fieldname,'Security key does not match');
        }
        return( $retval );
    }

    /**
     * Handles generation of &lt;input type='text' HTML field 
     * 
     * 
     * @param string $varname Name of the HTML field
     * @param string $value   value to be published into the field
     * @param string $class   CSS class (rarely used)
     * @returns string $html HTML that represents the field
     */
    function generator_newusername($varname,$value='',$class='')
    {
        return( $this->generator_textedit($varname,$value,$class) );
    }

    function validator_newusername($fieldname)
    {
        if( $this->validator_must_exist($fieldname) )
        {
            $value = $this->GetFormValue($fieldname);

            if( preg_match('/[^A-Za-z0-9_]/', $value) )
            {
                $this->SetFieldError($fieldname," must letters, numbers or underscore (_)");
                return(false);
            }

            if( strlen($value) > 25 )
            {
                $this->SetFieldError($fieldname," must be less than 25 characters");
                return(false);
            }

            $users =& CCUsers::GetTable();
            $user = $users->GetRecordFromName( $value );

            if( $user )
            {
                $this->SetFieldError($fieldname,"That username is already in use");
                return(false);
            }

            return( true );
        }

        return( false );
    }
}

/**
* Login form 
*/
class CCUserLoginForm extends CCUserForm
{
    function CCUserLoginForm()
    {
        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => 'Login Name',
                               'formatter'  => 'username',
                               'flags'      => CCFF_REQUIRED ),

                    'user_password' =>
                       array( 'label'       => 'Password',
                               'formatter'  => 'matchpassword',
                               'flags'      => CCFF_REQUIRED ),

                    'user_remember' =>
                       array( 'label'       => 'Remember Me',
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_NONE ),

                    '_new_user' =>
                       array( 'label'       => 'New User?',
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . ccl('register') . '">Click Here</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                    '_lost_password' =>
                       array( 'label'       => 'Lost Password?',
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . ccl('lostpassword') . '">Click Here</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                    );

        $this->AddFormFields( $fields );
        $this->SetSubmitText('Submit');
    }
}

/**
* Form for when user need a password reminder
*/
class CCLostPasswordForm extends CCUserForm
{
    function CCLostPasswordForm()
    {
        $this->CCUserForm();

        $fields = array( 
                    'user_name' =>
                        array( 'label'      => 'Login Name',
                               'form_tip'   => 'Leave this blank if you can not remember it.',
                               'formatter'  => 'username',
                               'flags'      => CCFF_NONE ),

                    'user_email' =>
                       array(  'label'      => 'e-mail',
                               'form_tip'   => 'A new password will be mailed to this address.',
                               'formatter'  => 'email',
                               'flags'      => CCFF_REQUIRED ),
                    '_new_user' =>
                       array( 'label'       => 'New User?',
                               'formatter'  => 'statictext',
                               'value'      => '<a href="' . ccl('register') . '">Click Here</a>',
                               'flags'      => CCFF_NONE | CCFF_NOUPDATE  | CCFF_STATIC),
                        );

        $this->AddFormFields( $fields );
        $this->SetSubmitText('Submit');
    }
}

/**
* General log in API and system event handler class
*/
class CCLogin
{
    /**
    * Event handler for building menus
    *
    * @see CCMenu::AddItems
    */
    function OnBuildMenu()
    {
        $items = array(
            'register'  => array( 'menu_text'  => 'Register',
                             'access'  => CC_ONLY_NOT_LOGGED_IN,
                             'menu_group' => 'artist',
                             'weight' => 5,
                             'action' => ccl('register')
                             ),


            'login'  => array( 'menu_text'  => 'Log In',
                             'access'  => CC_ONLY_NOT_LOGGED_IN,
                             'menu_group' => 'artist',
                             'weight' => 1,
                             'action' => ccl('login')
                             ),

            'logout'  => array( 'menu_text'  => 'Log Out',
                             'access'  => CC_MUST_BE_LOGGED_IN,
                             'menu_group' => 'artist',
                             'weight' => 72,
                             'action' => ccl('logout')
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
        CCEvents::MapUrl( 'register',       array( 'CCLogin', 'Register'),        CC_ONLY_NOT_LOGGED_IN );
        CCEvents::MapUrl( 'login',          array( 'CCLogin', 'Login'),           CC_ONLY_NOT_LOGGED_IN );
        CCEvents::MapUrl( 'logout',         array( 'CCLogin', 'Logout'),          CC_MUST_BE_LOGGED_IN );
        CCEvents::MapUrl( 'lostpassword',   array( 'CCLogin', 'LostPassword'),    CC_ONLY_NOT_LOGGED_IN );
        CCEvents::MapUrl( 's',              array( 'CCLogin', 'OnSecurityCallback'),  CC_DONT_CARE_LOGGED_IN );
    }

    /**
    * Puts up a registration for, handler for /register URL
    */
    function Register()
    {
        CCPage::SetTitle("Create A New Account");
        $form = new CCNewUserForm();

        if( empty($_POST['newuser']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $form->SetHiddenField( 'user_registered', date( 'Y-m-d H:i:0' ) );
            $form->GetFormValues($fields);
            $fields['user_real_name'] = $fields['user_name'];
            $users =& CCUsers::GetTable();
            $users->Insert($fields);
            CCPage::Prompt("OK, you are registered");
            CCMenu::Reset();
            $this->Login();
        }
    }

    /**
    * Handles /logout URL 
    */
    function Logout()
    {
        global $CC_GLOBALS;

        cc_setcookie(CC_USER_COOKIE,'',time());
        unset($_COOKIE[CC_USER_COOKIE]);
        CCPage::Prompt('You have been logged out');
        CCPage::SetTitle("Log Out");
        unset($CC_GLOBALS['user_name']);
        unset($CC_GLOBALS['user_id']);
        CCMenu::Reset();
    }

    /**
    * Handles /login URL, puts up log in form
    */
    function Login()
    {
        global $CC_GLOBALS;

        $form = new CCUserLoginForm();
        $form->SetHandler(ccl('login'));
        if( empty($_POST['userlogin']) || !$form->ValidateFields() )
        {
            CCPage::SetTitle("Log In");
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            $CC_GLOBALS = array_merge($CC_GLOBALS,$form->record);
            
            if( $form->GetFormValue('user_remember') == 1 )
                $time = time()+60*60*24*30;
            else
                $time = null;
            cc_setcookie(CC_USER_COOKIE,$CC_GLOBALS['user_name'],$time);
            CCMenu::Reset();
            $userapi = new CCUser();
            $userapi->UserPage($CC_GLOBALS['user_name']);
        }
    }

    /**
    * Handler for /lostpassword URL puts up form an responds to it (not implemented yet)
    */
    function LostPassword()
    {
        CCPage::SetTitle("Recover Lost Password");
        $form = new CCLostPasswordForm();
        if( empty($_POST['lostpassword']) || !$form->ValidateFields() )
        {
            CCPage::AddForm( $form->GenerateForm() );
        }
        else
        {
            CCPage::Prompt('Lost password would have been sent if this was implemented');
        }
    }

    /**
    * Digs around the cookies looking for an auto-login. If succeeds, populate CC_GLOBALS with user data
    */
    function InitCurrentUser()
    {
        global $CC_GLOBALS;

        if( !empty($_COOKIE[CC_USER_COOKIE]) )
        {
            $users =& CCUsers::GetTable();
            $record = $users->GetRecordFromName( $_COOKIE[CC_USER_COOKIE] );
            if( !empty( $record ) )
            {
                $CC_GLOBALS = array_merge($CC_GLOBALS,$record);
            }
        }
    }

    /**
    * Handles /s URL
    * 
    * This function does NOT return, it sends an image back to the browser then exits.
    * 
    * @see CCNewUserForm::generator_securitykey
    * @param integer $s Combination ID and index into a security key
    */
    function OnSecurityCallback($s)
    {
        $intval = intval($s);
        if( !$intval )
            exit;
        $key = intval($intval / 100);
        $offset = $intval % 100;
        $keys =& CCSecurityKeys::GetTable();
        $hash = $keys->QueryItemFromKey('keys_key',$key);
        $ip   = $keys->QueryItemFromKey('keys_ip',$key);
        if( empty($hash) || ($ip != $_SERVER['REMOTE_ADDR']) )
            exit;
        $ord  = ord($hash[$offset]);
        $fname = sprintf("ccimages/hex/f%x.png",$ord);
        header ("Content-Type: image/png");
        readfile($fname);
        exit;
    }

}

 


?>