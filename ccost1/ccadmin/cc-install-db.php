<?

// $Header$

if( !defined('IN_CC_HOST') )
   die('Welcome to CC Host');

function cc_install_tables(&$vars,&$msg)
{
    // 
    // USERS
    //
    $drops [] = 'cc_tbl_user';

    $sql[] = <<<END

CREATE TABLE cc_tbl_user 
    (
      user_id          int(5) unsigned NOT NULL auto_increment,
      user_name        varchar(25)     NOT NULL default '',
      user_real_name   varchar(255)    NOT NULL default '',
      user_password    tinyblob        NOT NULL default '',
      user_email       tinytext        NOT NULL default '',
      user_image       varchar(255)    NOT NULL default '',
      user_description mediumtext      NOT NULL default '',
      user_homepage    mediumtext      NOT NULL default '',
      user_registered  datetime        NOT NULL,
      user_favorites   mediumtext      NOT NULL default '',
      user_whatilike   mediumtext      NOT NULL default '',
      user_whatido     mediumtext      NOT NULL default '',
      user_lookinfor   mediumtext      NOT NULL default '',

      PRIMARY KEY user_id (user_id)
    )
END;

    // 
    // UPLOADS
    //
    $drops [] = 'cc_tbl_uploads';

    $sql[] = <<<END

CREATE TABLE cc_tbl_uploads 
    (
      upload_id         int(5) unsigned  NOT NULL auto_increment,
      upload_user       int(5) unsigned  NOT NULL,
      upload_contest    int(5) unsigned  NOT NULL,

      upload_name         varchar(255)     NOT NULL default '',
      upload_file_name    varchar(255)     NOT NULL default '',
      upload_license      varchar(255)     NOT NULL default '',
      upload_extra        mediumtext       NOT NULL default '',  
      upload_tags         mediumtext       NOT NULL default '',
      upload_date         datetime         NOT NULL,
      upload_description  mediumtext       NOT NULL default '',
      upload_filesize     int(20) unsigned NOT NULL,
      upload_published    int(1) unsigned  NOT NULL,

      PRIMARY KEY upload_id (upload_id)
    )

END;

    // 
    // (REMIX) TREE
    //
    $drops [] = 'cc_tbl_tree';

    $sql[] = <<<END

CREATE TABLE cc_tbl_tree 
    (
      tree_parent   int(5) unsigned,
      tree_child    int(5) unsigned
    )

END;

    // 
    // CONTESTS
    //
    $drops [] = 'cc_tbl_contests';
    $sql[] = <<<END

CREATE TABLE cc_tbl_contests
    (
      contest_id              int(5) unsigned  NOT NULL auto_increment,
      contest_user            int(5) unsigned  NOT NULL,
      contest_short_name      varchar(255)     NOT NULL default '',
      contest_friendly_name   varchar(255)     NOT NULL default '',
      contest_rules_file      varchar(255)     NOT NULL default '',
      contest_template        varchar(255)     NOT NULL default '',
      contest_bitmap          varchar(255)     NOT NULL default '',
      contest_description     text             NOT NULL default '',
      contest_deadline        datetime         NOT NULL,
      contest_created         datetime         NOT NULL,
      contest_auto_publish    int(1)           NOT NULL,
      contest_publish         int(1)           NOT NULL,
      contest_vote_online     int(1)           NOT NULL,
      contest_vote_deadline   datetime         NOT NULL,

      PRIMARY KEY contest_id (contest_id)
    )
END;

    // 
    // POLL
    //
    $drops [] = 'cc_tbl_polls';

    $sql[] = <<<END

CREATE TABLE cc_tbl_polls
    (
      poll_valueid  int(5) unsigned  NOT NULL auto_increment,
      poll_id       varchar(255),
      poll_value    varchar(255), 
      poll_numvotes int(5) unsigned NOT NULL default 0,

      PRIMARY KEY poll_valueid (poll_valueid)

    )

END;

    // 
    // TAGS
    //
    $drops [] = 'cc_tbl_tags';

    $sql[] = <<<END

CREATE TABLE cc_tbl_tags
    (
      tags_tag   varchar(50),
      tags_count int(5) unsigned,

      PRIMARY KEY tags_tag (tags_tag)
    )
END;

    // 
    // URL MAP 
    //
    $drops [] = 'cc_tbl_urlmap';

    $sql[] = <<<END

CREATE TABLE cc_tbl_urlmap
    (
      urlmap_urlfrom   mediumtext,
      urlmap_urlto     mediumtext
    )
END;
    // 
    // LICENSES
    //
    $drops [] = 'cc_tbl_licenses';

    $sql[] = <<<END

CREATE TABLE cc_tbl_licenses
    (
        license_id        varchar(255) NOT NULL,
        license_url       mediumtext   NOT NULL,
        license_name      varchar(255) NOT NULL,
        license_permits   mediumtext   NOT NULL,
        license_required  mediumtext   NOT NULL,
        license_prohibits mediumtext   NOT NULL,
        license_logo      varchar(255) NOT NULL,
        license_tag       varchar(255) NOT NULL,
        license_enabled   tinyint(1)   NOT NULL,
        license_strict    int(4)       NOT NULL,
        license_checked   tinyint(1)   NOT NULL,
        license_nic       varchar(255) NOT NULL,
        license_text      mediumtext   NOT NULL,

       PRIMARY KEY license_id (license_id)
    )
END;


    // 
    // KEYS
    //
    // used for new registration
    //
    $drops [] = 'cc_tbl_keys';

    $sql[] = <<<END

CREATE TABLE cc_tbl_keys
    (
      keys_id    int(5) unsigned  NOT NULL auto_increment,
      keys_key   varchar(255),
      keys_ip    varchar(40),
      keys_time  datetime,

      PRIMARY KEY keys_id (keys_id)
    )
END;

    // 
    // Configs
    //
    // used for various config arrays
    //
    $drops [] = 'cc_tbl_config';

    $sql[] = <<<END

CREATE TABLE cc_tbl_config
    (
      config_id     int(5) unsigned  NOT NULL auto_increment,
      config_type   varchar(255),
      config_scope  varchar(40),
      config_data   mediumtext,

      PRIMARY KEY config_id (config_id)
    )
END;

    CCDatabase::DBConnect();

    /* DROP PREVIOUS TABLES */
    $qr = mysql_query("SHOW TABLES");
    $tables = array();
    while( $row = mysql_fetch_row($qr) )
        $tables[] = $row[0];

    foreach( $drops as $drop )
    {
        if( in_array($drop,$tables) )
        {
            mysql_query( "DROP TABLE $drop" );
            $msg = mysql_error();
            if( $msg )
               return(false);
        }
    }

    /* INSTALL TABLES */
    foreach( $sql as $s )
    {
       mysql_query($s);
       $msg = mysql_error();
       if( $msg )
           return(false);
    }


    $default_licenses= array( 
                array( 'license_id' => 'sampling'   , 
                        'license_url' => 'http://creativecommons.org/licenses/sampling/1.0/',
                       'license_name' => 'Sampling',
                       'license_permits' => 'DerivativeWorks,Reproduction',
                       'license_prohibits' => '',
                       'license_required'  => 'Attribution,Notice',
                       'license_logo' => 'sampling.gif',
                       'license_tag' => 'sampling',
                       'license_enabled' => false,
                       'license_strict' => 5,
                       'license_checked' => '',
                       'license_nic' => 'sampling',
                       'license_text' => '<strong>Sampling</strong>: People can take and transform <strong>pieces</strong> of your work for any purpose other than advertising, which is prohibited. Copying and distribution of the <strong>entire work</strong> is also prohibited.</label>'
                       ),
                 array( 'license_id' => 'sampling+',
                        'license_url' => 'http://creativecommons.org/licenses/sampling+/1.0/',
                       'license_name' => 'Sampling Plus',
                       'license_permits' => 'Sharing,DerivativeWorks,Reproduction',
                       'license_prohibits' => '',
                       'license_required'  => 'Attribution,Notice',
                       'license_tag' => 'sampling_plus',
                       'license_enabled' => true,
                       'license_logo' => 'sampling+.gif',
                       'license_checked' => '',
                       'license_nic' => 'sampling+',
                       'license_strict' => 10,
                       'license_text' => '<strong>Sampling Plus</strong>: People can take and transform <strong>pieces</strong> of your work for any purpose other than advertising, which is prohibited. <strong>Noncommercial</strong> copying and distribution (like file-sharing) of the <strong>entire work</strong> are also allowed. Hence, "<strong>plus</strong>".'
                       ),
                 array( 'license_id' =>   'nc-sampling+',
                        'license_url' => 'http://creativecommons.org/licenses/nc-sampling+/1.0/',
                       'license_name' => 'Non-Commercial Sampling Plus',
                       'license_permits' => 'Distribution,DerivativeWorks,Reproduction',
                       'license_prohibits' => 'CommercialUse',
                       'license_required'  => 'Attribution,Notice',
                       'license_enabled' => true,
                       'license_tag' => 'nc_sampling_plus',
                       'license_logo' => 'nc-sampling+.gif',
                       'license_nic' => 'nc-sampling+',
                       'license_checked' => '',
                       'license_strict' => 20,
                       'license_text' => '<strong>Noncommercial Sampling Plus</strong>: People can take and transform <strong>pieces</strong> of your work for <strong>noncommercial</strong> purposes only. <strong>Noncommercial</strong> copying and distribution (like file-sharing) of the <strong>entire work</strong> are also allowed.'),
                 array( 'license_id' =>  'publicdomain' ,
                        'license_url' => 'http://creativecommons.org/licenses/publicdomain',
                       'license_name' => 'Public Domain',
                       'license_permits' => 'Reproduction,Distribution,DerivativeWorks',
                       'license_prohibits' => '',
                       'license_required'  => '',
                       'license_logo' => 'publicdomain.gif',
                       'license_tag' => 'public_domain',
                       'license_enabled' => false,
                       'license_strict' => 1,
                       'license_checked' => '',
                       'license_nic' => 'publicdomain',
                       'license_text' => '<strong>Public Domain</strong>: This choice suggests you want to dedicate your work to the public domain, the commons of information and expression where <strong>nothing is owned and all is permitted</strong>. The Public Domain Dedication is not a license. By using it, you do not simply carve out exceptions to your copyright; you grant your entire copyright to the public without condition. This grant is <strong>permanent and irreversible</strong>.'
                       ),
                 );

    $licenses =  new CCTable('cc_tbl_licenses','license_id');

    foreach( $default_licenses as $lic )
        $licenses->Insert($lic);

    $configs =& CCConfigs::GetTable();

    // -------------------- config -------------------------------

    $arr = array ( 
       'cookie-domain'       => $vars['cookiedom']['v'] , 
       'php-tal-dir'         => 'cclib/phptal/libs' , 
       'php-tal-cache-dir'   => 'cclib/phptal/phptal_cache',
       'user-upload-root'    => 'people' , 
       'contest-upload-root' => 'contests' , 
       'template-root'       => 'cctemplates/' , 
       'files-root'          => 'ccfiles/',
       'getid3-path'         => $vars['getid3']['v'] , 
       'getid3-v1'           => '1' , 
       'getid3-fileverify-enabled' => '1' , 

       'pretty-urls'         => '0',

        'phpbb2_enabled' => $vars['phpbb2_enabled']['v'],
        'phpbb2_root_path' => $vars['phpbb2_path']['v'],
        'phpbb2_forum_id' => $vars['phpbb2_forum_id']['v'],
        'phpbb2_admin_username' => $vars['phpbb2_admin_username']['v'],
    );

    $configs->SaveConfig( 'config', $arr, CC_GLOBAL_SCOPE);

    // ------------------ settings ---------------------------------

    $arr = array(
           'homepage'          => 'viewfile/home.xml' , 
           'style-sheet'       => 'cctemplates/skin-ccmixter.css' , 
           'page-template'     => 'page.xml' , 
           'admins'            => $vars['admin']['v'], 
           'thumbnail-x'       => '120px' , 
           'thumbnail-y'       => '120px' , 
           'upload-auto-pub'   => '1' , 
        );

    
    $configs->SaveConfig( 'settings', $arr, CC_GLOBAL_SCOPE);

    // ------------------- ttag --------------------------------

    $arr = array( 
            'site-title' =>  $vars['sitename']['v'], 
            'root-url' =>  $vars['rooturl']['v'], 
            'site-description' => $vars['site-description']['v'], 
            'footer' => <<<END
<br clear="both"><br /><br /><br />
This site is a product of <a href="http://sourceforge.net/projects/cctools/">CC Tools</a> project.<br />

This site uses <a href="http://getid3.sourceforge.net/">GetID3</a> and <a href="http://phptal.sourceforge.net/">PHPTal</a>.
<br /><br />
END
 , 
            'site-license' => <<<END
<!-- Creative Commons License -->

<a rel="license" href="http://creativecommons.org/licenses/by-nc/2.0/"><img alt="Creative Commons License" border="0" src="http://creativecommons.org/images/public/somerights20.gif" /></a><br />

This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc/2.0/">Creative Commons License</a>
END
        );

    $configs->SaveConfig( 'ttag', $arr, CC_GLOBAL_SCOPE);

    // -------------------- format-allow -------------------------------

    $arr = array( 
       'audio-aiff-aiff' => '1' , 
       'audio-au-au' => '1' , 
       'audio-flac-flac' => '1' , 
       'audio-mp3-mp3' => '1' , 
       'audio-ogg-vorbis' => '1' , 
       'audio-real-real' => '1' , 
       'audio-asf-wma' => '1' , 
       'archive-zip-' => '1' , 
       'image-gif-gif' => '1' , 
       'image-jpg-jpg' => '1' , 
       'image-png-png' => '1' , 
       'video-swf-swf' => '1'  );

    $configs->SaveConfig( 'format-allow', $arr, CC_GLOBAL_SCOPE);

    // -------------------- name-masks -------------------------------

    $arr = array( 
           'song'    => '%artist% - %filename%' , 
           'remix'   => '%source_title% (%title% %artist% Remix).%ext%' , 
           'contest' => '%contest% - %artist% - %filename%' , 
           'contest-source' => '%contest% - %filename%' ,
           'upload-replace-sp' => '1' , 
            );

    $configs->SaveConfig( 'name-masks', $arr, CC_GLOBAL_SCOPE);

    // -------------------- id3-tag-masks -------------------------------

    $arr = array( 
           'title' => '%title%' , 
           'artist' => '%artist%' , 
           'copyright' => '%Y% %artist% Licensed to the public under %license_url% Verify at %song_page%' , 
           'original_artist' => '%source_artist%' , 
           'remixer' => '%artist%' , 
           'year' => '%Y%' , 
           'url_user' => '%artist_page%' , 
           'album' => '%site%' 
        );

    $configs->SaveConfig( 'id3-tag-masks', $arr, CC_GLOBAL_SCOPE);

    // ---------------------------------------------------

    return(true);
}

?>