<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* This used for data exchange with file verify module $CC_UPLOAD_VALIDATOR
*
* You should only be interested in this if you actually write a verifier. 
* The shipping version of this code uses a wrapper for the the GetID3
* library to do that.
*
* @see CCUpload::PostProcessUploadNoUI
* @see CCFileVerify::FileValidate
*/
class CCFileFormatInfo
{
    var $_file_path;
    var $_errors;
    var $_warnings;
    var $_data;

    /**
    * Constructor
    *
    * @param string $file_path Local file to verify
    */
    function CCFileFormatInfo($file_path)
    {
        $this->_data        = array();
        $this->_errors      = array();
        $this->_warnings    = array();
        $this->_file_path   = $file_path;
    }
    
    /**
    * Returns the file path this was constructed with
    *
    * @returns string $file_path Local file to verify
    */
    function GetFilePath()
    {
        return( $this->_file_path );
    }

    /**
    * This is set if there were errors during validation
    *
    * @param mixed $mixed String or array of errors
    */
    function SetErrors($mixed)
    {
        if( is_array($mixed) )
            $this->_errors = array_merge($mixed,$this->_errors);
        else
            $this->_errors[] = $mixed;
    }

    /**
    * Returns any validation errors
    *
    * @return array $errors Error array
    */
    function GetErrors()
    {
        return( $this->_errors );
    }

    /**
    * This is set if there were warnings during validation
    *
    * @param mixed $mixed String or warnings of errors
    */
    function SetWarnings($mixed)
    {
        if( is_array($mixed) )
            $this->_warnings = array_merge($mixed,$this->_warnings);
        else
            $this->_warnings[] = $mixed;
    }


    /**
    * The verifier will set this to any format specific data that is to be stored in the database.
    *
    * Data set here will be set to the upload record. The verifier can retrieve it later
    * from the 'upload_extra' field.
    *
    * @param mixed $data Anything the verifier wants (typically formatting information)
    */
    function SetData($data)
    {
        $this->_data = $data;
    }

    /**
    * The verifier will set this to any format specific data that is to be stored in the database.
    *
    * @see GetData
    * @param mixed $data Anything the verifier wants (typically formatting information)
    */
    function GetData()
    {
        return( $this->_data );
    }


}


?>