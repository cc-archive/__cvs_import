<?
require_once("../modules/mod-utilities/globals.lib");

echo "

<html>
<body>

The first step in uploading a file is to select a Creative Commons License for your music. 

  ";

$url = "http://creativecommons.org/license/?partner=Mixter&exit_url=" . $GLOBALS['server_root'] . "cc-license-example-2?license_url=[license_url]%26license_name=[license_name]";

echo "

Click <A HREF=\"$url\">Here</A>

</body>
</html>
";