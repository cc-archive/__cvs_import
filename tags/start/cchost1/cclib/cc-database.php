<?
// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

$CC_SQL_DATE = '%W, %M %e, %Y @ %l:%i %p';

/**
* Wrapper class for mySQL, however only CCTable should be calling it directly.
*
* @see CCTable::CCTable
*/
class CCDatabase
{
    function DBClose()
    {
        $link =& CCDatabase::_link();
        if( $link )
        {
            //print("Closing: $link");
            @mysql_close($link);
            $link = null;
        }
    }

    /**
    * Static call to ensure connection to daemon. Can be called multiple times safely.
    */
    function DBConnect()
    {
        $config_db = CCDatabase::_config_db();
        include($config_db);
        $config = $CC_DB_CONFIG;

        $link = @mysql_connect( $config['db-server'], 
                                $config['db-user'], 
                                $config['db-password']) or die( mysql_error() );
        
        @mysql_select_db( $config['db-name'], $link ) or die( mysql_error() );

        return( $link );
    }

    /**
    * Performs one (or more) mySQL queries.
    *
    * @param mixed $sql single mySQL query or array of them
    */
    function Query( $sql )
    {
        if( is_array($sql) )
        {
            $retvals = array();
            foreach( $sql as $s )
                $retvals[] = CCDatabase::Query($s);
            return( $retvals );
        }

        $link =& CCDatabase::_link();
        if( empty($link) )
            $link = CCDatabase::DBConnect();

        $qr = mysql_query($sql,$link);
        if( !$qr )
        {
            if( CCDebug::IsEnabled() )
            {
                print( "<pre>$sql<br />" . mysql_error() . "</pre>");
                CCDebug::StackTrace(false);
            }
            else
            {
                trigger_error("Internal error, contact the super");
            }
        }
        return( $qr );
    }

    /**
    * Retrieves a single row. Use with SELECT statment.
    *
    * @param string $sql single mySQL SELECT statement
    * @param bool   $assoc TRUE means fetch_assoc, FALSE means fetch_row
    * @return array $row Row from database or null if results count greater or less than one.
    */
    function QueryRow( $sql, $assoc = true )
    {
        $qr = CCDatabase::Query($sql);

        if( mysql_num_rows($qr) != 1 )
            return( null );
        
        if( $assoc )
            return( mysql_fetch_assoc( $qr ) );
        else
            return( mysql_fetch_row( $qr ) );

    }

    /**
    * Retrieves a single item from a single row. Use with SELECT statment.
    *  
    * <code>
    *    $username = CCDatabase::QueryItem("SELECT username FROM users WHERE id = '9'");
    * </code>
    *  
    * @param string $sql mySQL SELECT statement with a single column
    * @return string $item First column results from SELECT statement
    */
    function QueryItem( $sql )
    {
        $row = CCDatabase::QueryRow($sql,false);
        return( $row[0] );
    }

    /**
    * Retrieves multiple rows. Use with SELECT statment.
    *  
    * <code>
        $rows =& CCDatabase::QueryRows("SELECT username, age FROM users WHERE age < 27");
        foreach( $rows as $row )
        {
            // ....
        }
    * </code>
    *
    * @param string $sql mySQL SELECT statement 
    * @return array $rows Array with database rows inside
    */
    function & QueryRows( $sql )
    {
        $qr = CCDatabase::Query($sql);
        $rows = array();
        while( $row = mysql_fetch_assoc($qr) )
            $rows[] = $row;

        return( $rows );
    }

    /**
    * Internal:  Returns the path to the current database config file
    *
    **/
    function _config_db()
    {
        static $CC_DB_INFO_FILE;
        if( empty($CC_DB_INFO_FILE) )
            return( 'cc-config-db.php' );
        return( $CC_DB_INFO_FILE );
    }

    /**
    * Internal:  Returns the link to the current connection
    *
    **/
    function & _link()
    {
        static $_link;
        return( $_link );
    }
}

?>