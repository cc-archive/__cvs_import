<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

CCEvents::AddHandler(CC_EVENT_GET_MACROS,   array( 'CCMacro' , 'OnGetMacros'));

class CCMacro
{
    /**
    * Event handler for getting renaming/id3 tagging macros
    *
    * @param array $record Record we're getting macros for (if null returns documentation)
    * @param array $patterns Substituion pattern to be used when renaming/tagging
    * @param array $mask Actual mask to use (based on admin specifications)
    */
    function OnGetMacros(&$dummy, &$patterns, &$dummy2)
    {
        if( empty($dummy) )
        {
            $patterns['%%'] = 'Percent sign (%)';
            $patterns['%Y%'] = 'Current year (' . date('Y') . ')';
            $patterns['%d%'] = 'Current day (' . date('d') . ')';
            $patterns['%m%'] = 'Current month (' . date('m') . ')';
        }
    }

    function TranslateMask($patterns,$mask,$replace_sp = false)
    {
        $patterns['%%']  = '%';
        $patterns['%Y%'] = date('Y');
        $patterns['%d%'] = date('d');
        $patterns['%m%'] = date('m');

        $regex = array();
        $replacesments = array();
        foreach( $patterns as $r => $repl )
        {
            $regex[] = '/' . $r . '/';
            $replacements[] = $repl;
        }
        
        $result = preg_replace( $regex, $replacements, $mask );

        if( $replace_sp )
            $result = str_replace(' ','_',$result);

        return($result);
    }
}
?>