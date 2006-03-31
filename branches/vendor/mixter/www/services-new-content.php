<?
// ----------------------------
// Matthew Drake and Ian Spivey
// Mixter Website
// 6.171
// madrake@mit.edu, ispivey@mit.edu
// ----------------------------
// Page: new-content
// Purpose: A SOAP service for getting new content off our website.
// ----------------------------

require_once("../modules/mod-utilities/page-layout.lib");
require_once("../modules/mod-users/users-interface.php");
require_once("../modules/mod-content/content-interface.php");
require_once("../modules/mod-external-libraries/nusoap/nusoap.php");
$s = new soap_server;

$NAMESPACE = $GLOBALS['server_root'];

$s->configureWSDL('NewContent', $NAMESPACE);
$s->wsdl->schemaTargetNamespace = $NAMESPACE;

$s->wsdl->addComplexType(
  'Content',
  'complexType',
  'struct',
  'all',
  '',
  array("title" => array('name' => 'title', 'type' => 'xsd:string'),
        "summary" => array('name' => 'summary', 'type' => 'xsd:string'),
        "creator" => array('name' => 'creator', 'type' => 'xsd:string'),
        "creation_date" => array('name' => 'creation_date', 'type' => 'xsd:string'),
        "content_type" => array('name' => 'content_type', 'type' => 'xsd:string')
  ));

$s->wsdl->addComplexType(
    'ContentArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:Content[]')
    ),
    'tns:Content'
);


$s->register('new_content', 
  array('num_content_items' => 'xsd:int'),
  array('return' => 'tns:ContentArray'),
  $NAMESPACE);

function new_content($n) {
  if (!is_int($n) || ($n > 100)) {
    return new soap_fault('Client', '', 'Must supply a valid number of content items.');
  }
  return getRecentContentRPC($n);
}
$s->service($HTTP_RAW_POST_DATA);
