<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

/**
* Helper API for debugging the app
*
*/
class CCDebug
{
    /**
    * Dis/Enables debugging flag
    *
    * @see IsEnabled
    * @param bool $bool true means enable
    */
    function Enable($bool)
    {
        $states =& CCDebug::_states();
        $states['enabled'] = $bool;
        ini_set('display_errors',         $bool );
        ini_set('display_startup_errors', $bool );
        ini_set('log_errors',             !$bool );
        //if( !$bool )
        {
            $error_handler = 'cc_error_handler';
            set_error_handler($error_handler);
            $states['error_handler'] = $error_handler;
        }
    }

    /**
    * Get current state of debugging flag
    *
    * @see Enable
    * @return bool $bool true means enabled
    */
    function IsEnabled()
    {
        $states =& CCDebug::_states();
        return( isset($states['enabled']) && ($states['enabled'] === true) );
    }

    /**
    * Use this to wrap calls to lax 3rd party libraries 
    *
    * @see RestoreErrors
    */
    function QuietErrors()
    {
        $states =& CCDebug::_states();
        $states['error_reporting'] = error_reporting(0);
        if( $states['error_handler'] )
            restore_error_handler();
    }

    /**
    * Use this to wrap calls to lax 3rd party libraries 
    *
    * @see QuietErrors
    */
    function RestoreErrors()
    {
        $states =& CCDebug::_states();
        error_reporting( $states['error_reporting']  );
        if( !empty($states['error_handler']) )
            set_error_handler($states['error_handler'] );
    }


    /**
    * Displays a stack track from where ever this a call to this is placed
    *
    * @param bool $template_safe true means you are NOT debugging code that displays HTML
    */
    function StackTrace($template_safe=true)
    {
        if( !CCDebug::IsEnabled() )
            return;

        if (function_exists("debug_backtrace")) 
        {
            $st = debug_backtrace();
        }
        else
        {
            $st = "No stack trace in this vesion of php";
        }

        CCDebug::PrintVar($st,$template_safe);
    }

    /**
    * The most useful function in the entire codbase. Display any variable for debugging
    *
    * Use this method with any variable (include globals like $_REQUEST)
    *
    * This method EXITS THE SESSION (!!)
    *
    * This method is disabled when debugging is not enabled.
    *
    * @param mixed $var Reference to variable to dump to screen
    * @param bool $template_safe true means you are NOT debugging code that displays HTML
    */
    function PrintVar(&$var, $template_safe = true)
    {
        if( !CCDebug::IsEnabled() )
            return;

        $t =& CCDebug::_textize($var);

        $html = '<pre style="font-size: 10pt;">' .
                htmlspecialchars($t) .
                '</pre>';

        if( $template_safe )
        {
            CCPage::PrintPage( $html );
        }
        else
        {
            print("<html><body>$html</body></html>");
        }
        exit;
    }

    /**
    * The second most useful function in the entire codbase. Display any variable for debugging
    *
    * Dumps the results to /cc-log.txt in the main directory of the site. 
    * Use this method with any variable (include globals like $_REQUEST).
    *
    * This method is disabled when debugging is not enabled.
    *
    * @param string $msg Use this string to identify what your actually dumping into the log
    * @param $var Reference to variable to dump to screen
    */
    function LogVar($msg, &$var)
    {
        if( !CCDebug::IsEnabled() )
            return;

        $t =& CCDebug::_textize($var);

        CCDebug::Log('[' . $msg . '] ' . $t);
    }

    /**
    * Write stuff to a log
    *
    * Dumps the results to /cc-log.txt in the main directory of the site. 
    *
    * @param string $msg Use this string to identify what your actually dumping into the log
    */
    function Log($msg)
    {
        if( !CCDebug::IsEnabled() )
            return;
        $f    = fopen('cc-log.txt', 'a+' );
        $ip   = CCUtil::IsHTTP() ? $_SERVER['REMOTE_ADDR'] : 'cmdline';
        $msg = '[' . $ip . ' - ' . date("Y-m-d h:i a") . '] ' . $msg . "\n";
        fwrite($f,$msg,strlen($msg));
        fclose($f);
    }

    /**
    *  Works exactly like a stop watch, ie. starts if stopped and stops if started
    *
    * Based on http://us2.php.net/microtime#50277
    *
    * Call the function a first time to start the chronometer. The next call to the function will return the number of
    * milliseconds elapsed since the chronometer was started (rounded to three decimal places). The next call will start 
    * the chronometer again from where it finished. Multiple timers can be used by creating multiple $timer variables.
    *
    * <code>

        chronometer($timer1);
        // DO STUFF HERE
        chronometer($timer2);
        chronometer($timer3);
        // DO MORE STUFF
        echo chronometer($timer1);
        // DO SOMETHING
        echo chronometer($timer3);
        // DO SOMETHING
        echo chronometer($timer2);

    * </code>
    *
    * @param mixed $CHRONO_STARTTIME Reference to timer var (does not need to be declared or initialized before use)
    * @returns float $result Void if starting timer, string (in seconds) formatted
    */
    function Chronometer(&$CHRONO_STARTTIME)
    {
       $now = (float) array_sum( explode(' ', microtime()) );
      
       if(isset($CHRONO_STARTTIME['running']))
       {
           if($CHRONO_STARTTIME['running'])
           {
               /* Stop the chronometer : return the amount of time since it was started,
               in ms with a precision of 4 decimal places.
               We could factor the multiplication by 1000 (which converts seconds
               into milliseconds) to save memory, but considering that floats can
               reach e+308 but only carry 14 decimals, this is certainly more precise */
              
               $CHRONO_STARTTIME['elapsed'] += round($now - $CHRONO_STARTTIME['temp'], 4);
               $CHRONO_STARTTIME['running'] = false;

              
               return number_format($CHRONO_STARTTIME['elapsed'],4);
           }
           else
           {
               $CHRONO_STARTTIME['running'] = true;
               $CHRONO_STARTTIME['temp'] = $now;
           }
       }
       else
       {
           // Start the chronometer : save the starting time
          
           $CHRONO_STARTTIME = array();
           $CHRONO_STARTTIME['running'] = true;
           $CHRONO_STARTTIME['elapsed'] = 0;
           $CHRONO_STARTTIME['temp'] = $now;
       }
    }
    /**
    * Internal buddy
    */
    function & _textize(&$var)
    {
        ob_start();
        if( is_array($var) || is_object($var) || is_resource($var) )
            print_r($var);
        else
            var_dump($var);
        $t = ob_get_contents();
        ob_end_clean();

        return($t);
    }

    /**
    * Internal buddy
    */
    function & _states()
    {
        static $_error_states;
        if( !isset($_error_states) )
            $_error_states = array();
        return( $_error_states );
    }

}

function cc_error_handler($errno, $errstr='', $errfile='', $errline='', $errcontext=null)
{
    $date = date("Y-m-d H:i a");
    $ip   = isset($_SERVER) ? $_SERVER['REMOTE_ADDR'] : 'cmdline';
    $err  = "\"$errfile\"($errline): $errstr ($date/$ip)\n";
    $f    = fopen('cc-errors.txt', 'a+' );
    fwrite($f,$err,strlen($err));
    fclose($f);

    readfile('cc-error-msg.txt');
    exit;
}


?>