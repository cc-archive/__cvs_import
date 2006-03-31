<?php

/*
 *  $Id$
 *  $Date$
 *  copyright 2005, Nathan R. Yergler, Creative Commons
 *  
 *  Licensed under the GNU GPL v2
 *  see docs/LICENSE for more information
 *
 *  Provides proxy classes to the CC web services for domain-restricted 
 *  Javascript applications.
 * 
 */

require_once(dirname(__FILE__).'/minixml.inc.php');

$WS_ROOT = "http://api.creativecommons.org/rest/dev/";

function licenseClasses_xml() {
   global $WS_ROOT;
   
   // retrieve the license 
   $xml = file_get_contents($WS_ROOT);

   return $xml;

} // licenseClasses_xml

function licenseClasses() {
   $l_classes = array();

   // retrieve the license 
   $xml = licenseClasses_xml();

   // parse the classes into a hash
   $xmlDoc = new MiniXMLDoc();
   $xmlDoc->fromString($xml);

   $root =& $xmlDoc->getRoot();
   $root =& $root->getElement('licenses');
   $licenses = $root->getAllChildren('license');

   foreach ($licenses as $l) {
      $l_classes[strval($l->attribute('id'))] = strval($l->getValue());
   }

   return $l_classes;

} // licenseClasses

function licenseQuestions($lclass) {
   global $WS_ROOT;

   $uri = $WS_ROOT.'license/'.$lclass."/";
   $questions = array();

   // retrieve the license 
   $xml = file_get_contents($uri);
 
   // parse the classes into a hash
   $xmlDoc = new MiniXMLDoc();
   $xmlDoc->fromString($xml);

   $root =& $xmlDoc->getRoot();
   $root =& $root->getElement('licenseclass');
   $fields = $root->getAllChildren('field');

   foreach ($fields as $field) {
    $f_id = strval($field->attribute('id'));
    $questions[$f_id] = array();

    $el =& $field->getElement('label');
    $questions[$f_id]['label'] = strval($el->getValue());

    $el =& $field->getElement('description');
    $questions[$f_id]['description'] = strval($el->getValue());

    $el =& $field->getElement('type');
    $questions[$f_id]['type'] = strval($el->getValue());

    $questions[$f_id]['options'] = array();

    foreach ($field->getAllChildren('enum') as $enum) {
       $el =& $enum->getElement('label');
       $questions[$f_id]['options'][(string)$enum->attribute('id')] = (string)$el->getValue();
    } // for each enum

   } // foreach

   return $questions;
} // licenseQuestions

function getLicenseQuestions($class) {

   $result = '<table>';
   $fields = licenseQuestions($class);

   foreach ($fields as $f_id=>$f_data) {
      $result .= '<tr><th><nobr>' . $f_data['label'] . '</nobr></th><td>';

      // generate the appropriate widget
      if ($f_data['type'] == 'enum') {
         $result .= '<select class="lic_q" id="'.$f_id.'" lic_q="true" size="1">';

         foreach ($f_data['options'] as $enum_id=>$enum_val) {
            $result .= '<option value="'. $enum_id . '">' . $enum_val . '</option>';
         } // for each option

         $result .= '</select>';

      } // if type is enumeration
      $result .= '</td></tr>';
   } // for each field...

   $result .= '</table>';

   return $result;

} // getLicenseQuestions

function issueLicense($lic_class, $answers) {

   global $WS_ROOT;
   $result = array();

   // do some brain-dead validation on $answers
   if (!isset($answers['jurisdiction'])) {
      $answers['jurisdiction'] = '-';
   } 

   // assemble the answers XML fragment
   $answers_xml = "<answers><license-" . $lic_class . ">";

   foreach ($answers as $field_id=>$value) {
      $answers_xml .= "<" . $field_id . ">" . $value . "</" . $field_id . ">";
   } // for each answer

   $answers_xml .= "</license-" . $lic_class . "></answers>";

   // make the web service request
   $uri = $WS_ROOT."license/" . $lic_class . "/issue?answers=" . urlencode($answers_xml);
   $xml = file_get_contents($uri);

   // extract the license information
   $xmlDoc = new MiniXMLDoc();
   $xmlDoc->fromString($xml);

   $root =& $xmlDoc->getRoot();
   $root =& $root->getElement('result');

   $el =& $root->getElement('license-uri');
   $result["uri"] = strval($el->getValue());

   $el =& $root->getElement('license-name');
   $result["name"] = strval($el->getValue());

   $el =& $root->getElement('rdf');
   $result["rdf"] = $el->toString();

   // use a regexp to extract the HTML to avoid any problems with miniXml
   preg_match("/(<html>)([\s\S]*)(<\/html>)/", $xml, $matches);
   $result["html"] = $matches[2];

   return $result;
} // issueLicense


function getLicense($lclass, $answer_string) {

   $answers = array();

   $as_vals = explode(",", $answer_string);

   foreach ($as_vals as $a) {
      list($key, $val) = explode(":", $a);
      $answers[$key] = $val;
   } 

   // get the license html, rdf, etc.
   $license_info = issueLicense($lclass, $answers);
   $flatten = array();

   foreach ($license_info as $field=>$value) {
      $flatten[] = "$field:$value";
   } // for each bit of license information

   return implode(";", $flatten);
} // getLicense


if (isset($_POST['func'])) {

ob_start();

$target = $_POST['func'];

if ($target == 'questions') {
   echo getLicenseQuestions($_POST['class']);
} else
if ($target == 'issue') {
   echo getLicense($_POST['class'], $_POST['answers']);
} // issue
else
if  ($target == 'classes'){
   echo licenseClasses();
}
ob_end_flush();
}

?>
