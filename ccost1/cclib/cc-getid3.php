<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

define('CCGETID3_PATH_KEY',               'getid3-path'); 
define('CCGETID3_FILEVERIFY_ENABLED_KEY', 'getid3-fileverify-enabled');
define('CCGETID3_FILETAGGER_ENABLED_KEY', 'getid3-filetagger-enabled');
define('CCGETID3_ENABLED_ID3V1',          'getid3-v1');

CCEvents::AddHandler(CC_EVENT_APP_INIT,   '_verify_getid3_install' );
CCEvents::AddHandler(CC_EVENT_GET_CONFIG_FIELDS,  array( 'CCGetID3' , 'OnGetConfigFields' ));

// todo: contact getID3() folks to aks why this function is missing
// from their libs...
function IsValidDottedIP($str) { return(true); }


/**
* Wrapper for the GetID3 Library
*
*/
class CCGetID3
{
    /**
    * Called internally when we cant find the installation of GetID3
    *
    */
    function BadPath()
    {
        global $CC_GLOBALS;
        $getid3path = $CC_GLOBALS[CCGETID3_PATH_KEY] . '/getid3.php';
        $msg = 'GetID3 library integration are not properly installed: <br />path does not exists<br />';

        if( CCUser::IsAdmin() )
            $msg .= '<a href="' . ccl('admin','verify') . '">click here</a> to edit configuration';
        else
            $msg .= 'Please ask the site administrator to correct this';

        CCPage::SystemError($msg);
    }

    /**
    * Called internally when GetID3 is not properly installed 
    *
    */
    function NotConfigured()
    {
        CCPage::SystemError("GetID3 library integration are not properly installed: <br />".
                          "Please ask the site administrator to configure properly.");
    }

    /**
    * Initialize the GetID3 library
    * 
    * Sets the following parameters:
    * 
    * <code>
            $ID3Obj = new getID3;
            $ID3Obj->option_tag_lyrics3       = false;
            $ID3Obj->option_tag_apetag        = false;
            $ID3Obj->option_tags_process      = true;
            $ID3Obj->option_tags_html         = false;
    * 
    * </code>
    * 
    * @returns object $id3obj Initialized GetID3 library object
    */
    function & InitID3Obj()
    {
        static $ID3Obj;

        if( empty($ID3Obj) )
        {
            $ID3Obj = new getID3;
            $ID3Obj->option_tag_lyrics3       = false;
            $ID3Obj->option_tag_apetag        = false;
            $ID3Obj->option_tags_process      = true;
            $ID3Obj->option_tags_html         = false;
        }

        return($ID3Obj);
    }

    /**
    * Get the default formats handled by the library
    *
    * Returns an array of the following structure:
    * 
    * <code>
         $file_formats['audio-aiff-aiff'] =  array(
           'name'        => 'aif',
           'description' => 'AIFF Audio',
           'enabled'     => true,
           'mediatype'   => 'audio',
           );
    * </code>
    * 
    * @returns array $formats Array of format info structures
    */
    function & GetFormats()
    {
        static $file_formats;

        if( !empty($file_formats) )
            return($file_formats);

         $file_formats = array();

         $file_formats['audio-aiff-aiff'] =  array(
           'name'       => 'aif',
           'description' => 'AIFF Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-au-au'] =  array(
           'name'       => 'au',
           'description' => 'Java (AU) Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-flac-flac'] =  array(
           'name'       => 'flac',
           'description' => 'FLAC Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-mp3-mp3'] =  array(
           'name'       => 'mp3',
           'description' => 'MP3 Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-ogg-vorbis'] =  array(
           'name'       => 'ogg',
           'description' => 'OGG/Vorbis Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-real-real'] =  array(
           'name'       => 'rm',
           'description' => 'Real Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-riff-wav'] =  array(
           'name'       => 'wav',
           'description' => 'WAV (Riff) Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['audio-asf-wma'] =  array(
           'name'       => 'wma',
           'description' => 'Windows Media Audio',
           'enabled' => true,
           'mediatype' => 'audio',
           );
         $file_formats['archive-zip-'] =  array(
           'name'       => 'zip',
           'description' => 'ZIP Archive',
           'enabled' => true,
           'mediatype' => 'Archive',
           );
         $file_formats['video-riff-avi'] =  array(
           'name'       => 'avi',
           'description' => 'Windows Video',
           'enabled' => true,
           'mediatype' => 'video',
           );
         $file_formats['video-quicktime-quicktime'] =  array(
           'name'       => 'mov',
           'description' => 'Quicktime Video',
           'enabled' => true,
           'mediatype' => 'video',
           );
         $file_formats['video-real-real'] =  array(
           'name'       => 'rmvb',
           'description' => 'Real Video',
           'enabled' => true,
           'mediatype' => 'video',
           );
         $file_formats['video-asf-wmv'] =  array(
           'name'       => 'wmv',
           'description' => 'Windows Media Video',
           'enabled' => true,
           'mediatype' => 'video',
           );
         $file_formats['image-bmp-bmp'] =  array(
           'name'       => 'bmp',
           'description' => 'Windows BMP Image',
           'enabled' => true,
           'mediatype' => 'image',
           );
         $file_formats['image-gif-gif'] =  array(
           'name'       => 'gif',
           'description' => 'GIF Image',
           'enabled' => true,
           'mediatype' => 'image',
           );
         $file_formats['image-jpg-jpg'] =  array(
           'name'       => 'jpg',
           'description' => 'JPG Image',
           'enabled' => true,
           'mediatype' => 'image',
           );
         $file_formats['image-png-png'] =  array(
           'name'       => 'png',
           'description' => 'PNG Image',
           'enabled' => true,
           'mediatype' => 'image',
           );
         $file_formats['video-swf-swf'] =  array(
           'name'       => 'swf',
           'description' => 'Flash Video',
           'enabled' => true,
           'mediatype' => 'video',
           );

         return( $file_formats );

      }

    /**
    * Callback for GET_CONFIG_FIELDS event
    *
    * Add global settings to config editing form
    */
    function OnGetConfigFields($scope,&$fields)
    {
        if( $scope == CC_GLOBAL_SCOPE )
        {
            $fields[CCGETID3_FILEVERIFY_ENABLED_KEY] =
                            array( 'label'      => 'GetID3 File Verify Enabled',
                                   'formatter'  => 'checkbox',
                                   'flags'      => CCFF_POPULATE);

            $fields[CCGETID3_PATH_KEY] =
                            array( 'label'      => 'Path to GetID3 Library',
                                   'formatter'  => 'textedit',
                                   'form_tip'   => 'Local server path to library (e.g. /usrer/lib/getid3/getid3)',
                                   'flags'      => CCFF_POPULATE | CCFF_REQUIRED  );

           $fields[CCGETID3_ENABLED_ID3V1] =
                        array( 'label'      => 'Tag ID3v1',
                               'form_tip'   => 'Tag old style v1 tags as well as v2',
                               'formatter'  => 'checkbox',
                               'flags'      => CCFF_POPULATE );
        }
    }
}


function _verify_getid3_install()
{
    global $CC_GLOBALS;

    if( empty($CC_GLOBALS[CCGETID3_PATH_KEY]) )
    {
        CCEvents::AddHandler(CC_EVENT_APP_INIT, array( 'CCGetID3', 'NotConfigured') );
    }
    else
    {
        $getid3path = $CC_GLOBALS[CCGETID3_PATH_KEY] . '/getid3.php';
        if( !file_exists($getid3path) )
        {
           CCEvents::AddHandler(CC_EVENT_APP_INIT, array( 'CCGetID3', 'BadPath') );
        }
        else
        {
            require_once( $getid3path );
        }
    }

}



?>