<?
// $Header$

function CC_micro_now()      { return (float) array_sum( explode(' ', microtime()) ); }
function CC_micro_diff()     { global $exec_start; return CC_micro_now() - $exec_start; }
function CC_micro_diff_fmt() { return number_format(CC_micro_diff(),4); }
$exec_start = CC_micro_now();

error_reporting(E_ALL);

if( !file_exists('cc-config-db.php') )
{
    print('<html><body>CC Host has not been properly installed</body></html>');
    exit;
}

if( file_exists('ccadmin') )
{
    print('<html><body>Installation is not complete. Please rename the /ccadmin directory.</body></html>');
    exit;
}

define('IN_CC_HOST', true);

require_once('cc-includes.php');
require_once('cc-custom.php');

CCDebug::Enable(true);
CCConfigs::cc_init_config(); 
CCEvents::Invoke(CC_EVENT_APP_INIT);
CCEvents::PerformAction();
CCPage::Show();

?>