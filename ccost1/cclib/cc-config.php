<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

$CC_GLOBALS   = array();
$CC_CFG_ROOT  = '';
$CC_SETTINGS  = array();

/**
*  Wrapper for config table
*
*/
class CCConfigs extends CCTable
{
    /**
    * Constructor (should not be used, use GetTable() instead)
    *
    * @see CCConfigs::GetTable
    */
    function CCConfigs()
    {
        $this->CCTable('cc_tbl_config','config_id');
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
        static $table;
        if( !isset($table) )
            $table = new CCConfigs();
        return($table);
    }

    /**
    * Get the configuration settings of a type for a given scope
    *
    * Configuration settings are grouped by 'type' within a given 'scope'
    *
    * Type can be 'config' (which only applies to the global scope) or
    * things like 'menu', 'licenses', 'formats-allowed', or whatever
    * a given module wants to store here.
    *
    * Scope is either CC_GLOBAL_SCOPE or a custom scope determined
    * by the user (typically the site's admin). The scope is determined
    * by the first part of the url after the base.
    *
    * http://cchost.org?mode=/myscope/somecommand/param
    *
    * In this case 'myscope' is the scope used to retrive the given
    * settings values.
    *
    * The global scope is called 'main' in the URL.
    *
    * If a given type is requested for a non-global scope then the
    * values for that type in CC_GLOBAL_SCOPE (main) is used.
    *
    * @param string $type Type of data being requested
    * @param string $scope Scope being requested. If null the current scope is used.
    * @returns array Array containing variables matching parameter's request
    */
    function GetConfig($type,$scope = '')
    {
        global $CC_CFG_ROOT;
        if( empty($scope) )
            $scope = $CC_CFG_ROOT;

        $where['config_type'] = $type;
        $where['config_scope'] = $scope;
        $arr = $this->QueryItem('config_data',$where);
        if( $arr )
            $arr = unserialize($arr);
        elseif( $scope != CC_GLOBAL_SCOPE )
            return( $this->GetConfig($type,CC_GLOBAL_SCOPE) );
        else
            $arr = array();
        return( $arr );
    }

    /**
    * Save an array of settings of a given type and assign it to a scope
    *
    * @see GetConfig
    * 
    * @param string $type Type of data being saved (e.g. 'config', 'menu', etc.)
    * @param array  $arr  Name/value pairs in array to be saved
    * @param string $scope Scope to assigned to. If null the current scope is used. If $type is 'config' it is ALWAYS saved to CC_GLOBAL_SCOPE
    */
    function SaveConfig($type,$arr,$scope='',$merge = true)
    {
        global $CC_CFG_ROOT;

        if( $type == 'config' )
            $scope = CC_GLOBAL_SCOPE;
        elseif( empty($scope) )
            $scope = $CC_CFG_ROOT;

        $where['config_type'] = $type;
        $where['config_scope'] = $scope;
        $key = $this->QueryKey($where);
        $where['config_data'] = serialize($arr);
        if( $key )
        {
            $where['config_id'] = $key;
            if( $merge )
            {
                $old = $this->QueryItemFromKey('config_data', $key);
                $old = unserialize($old);
                $where['config_data'] = serialize(array_merge($old,$arr));
            }

            $this->Update($where);
        }
        else
        {
            $this->Insert($where);
        }
    }

    /**
    * Internal helper for initializes globals
    *
    */
    function cc_init_config()
    {
        global $CC_GLOBALS, $CC_CFG_ROOT, $CC_HOST_VERSION;

        $configs =& CCConfigs::GetTable();
        $CC_GLOBALS = $configs->GetConfig('config', CC_GLOBAL_SCOPE);
        $CC_GLOBALS['version'] = $CC_HOST_VERSION;
        $regex = '%/([^/\?]+)%';

        if( $CC_GLOBALS['pretty-urls'] )
        {
             preg_match_all($regex,CCUtil::StripText($_SERVER['REQUEST_URI']),$a);
             array_shift($a[1]);
             $A =& $a[1];
        }
        else
        {
             preg_match_all($regex,CCUtil::StripText($_REQUEST['mode']),$a);
             $A =& $a[1];
        }

        $CC_CFG_ROOT = empty($A[0]) ? CC_GLOBAL_SCOPE : $A[0];

        $CC_GLOBALS['home-url'] = ccl();
    }


}
 
?>