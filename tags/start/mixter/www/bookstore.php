<?php

require_once('../modules/mod-external-libraries/nusoap/nusoap.php');
$parameters = array('ispivey');
$soapclient = new soapclient('http://soap.amazon.com/schemas3/AmazonWebServices.wsdl','wsdl');
echo $soapclient->call('hello',$parameters);

?>
