<?

// $Header$ 

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

$CC_HOST_VERSION = '0.0.1';

define( 'CC_GLOBAL_SCOPE', 'main' );
define( 'CC_LOCAL_SCOPE',  'local' );

define('CC_1MG', 1024 * 1024);

define('CC_USER_COOKIE', 'xi1gk');

// Access flags
define('CC_MUST_BE_LOGGED_IN',   1 );
define('CC_ONLY_NOT_LOGGED_IN',  2 );
define('CC_DONT_CARE_LOGGED_IN', 4 );
define('CC_ADMIN_ONLY',          8 );
define('CC_OWNER_ONLY',          0x10 );


// system events
define('CC_EVENT_APP_INIT',            'init');
define('CC_EVENT_MAIN_MENU',           'mainmenu');
define('CC_EVENT_UPLOAD_MENU',         'uploadmenu');
define('CC_EVENT_UPLOAD_ROW',          'uploadrow' );
define('CC_EVENT_UPLOAD_LISTING',      'uploadlisting' );
define('CC_EVENT_NEW_UPLOAD',          'newupload' );
define('CC_EVENT_FINALIZE_UPLOAD',     'finalizeupload' );
define('CC_EVENT_GET_SYSTAGS',         'getsystags' );
define('CC_EVENT_USER_ROW',            'userrow' );
define('CC_EVENT_MAP_URLS',            'mapurls');
define('CC_EVENT_CONTEST_MENU',        'contestmenu' );
define('CC_EVENT_CONTEST_ROW',         'contestrow' );
define('CC_EVENT_GET_MACROS',          'getmacros' );
define('CC_EVENT_GET_UPLOAD_FIELDS',   'getupflds' );
define('CC_EVENT_GET_USER_FIELDS',     'getuserflds' );
define('CC_EVENT_GET_CONFIG_FIELDS',   'getcfgflds' );
define('CC_EVENT_DELETE_UPLOAD',       'delete' );

//
// menu action flag
define('CC_MENU_DISPLAY', 1);
define('CC_MENU_EDIT',    2);

//
// form flags
define('CCFF_NONE',             0);
define('CCFF_SKIPIFNULL',     0x01); // insert/update - GetFormValues
define('CCFF_NOUPDATE',       0x02); // insert/update

define('CCFF_POPULATE',       0x04); // populate - PopulateValues

define('CCFF_HIDDEN',         0x08); // html form - GenerateForm

define('CCFF_REQUIRED',       0x20); // validate - ValidateFields
define('CCFF_NOSTRIP',        0x40); // validate
define('CCFF_NOADMINSTRIP',   0x80); // validate
define('CCFF_STATIC',        0x100); // validate

define('CCFF_HIDDEN_DEFAULT',  CCFF_HIDDEN | CCFF_POPULATE);


// upload descriptors/system tags
define('CCUD_ORIGINAL',  'original');
define('CCUD_REMIX',     'remix');
define('CCUD_SAMPLE',    'sample');

define('CCUD_MEDIA_BLOG_UPLOAD',     'media');

define('CCUD_CONTEST_MAIN_SOURCE',   'contest_source');
define('CCUD_CONTEST_SAMPLE_SOURCE', 'contest_sample');
define('CCUD_CONTEST_ALL_SOURCES',   'contest_sample,contest_source');
define('CCUD_CONTEST_ENTRY',         'contest_entry');
define('CCUD_CONTEST_ALL',           'contest_entry,contest_sample,contest_source');

define('CCUD_GENERAL_UPLOADS', 'original,remix,sample');
define('CCUD_USER_UPLOADS',    ''); // no filter (!)

// search criteria flags
define( 'CC_SEARCH_USERS', 1 );
define( 'CC_SEARCH_UPLOADS', 2 );
define( 'CC_SEARCH_ALL',  CC_SEARCH_USERS | CC_SEARCH_UPLOADS);

// menu flags
define( 'CCMF_CUSTOM', 1 );

?>