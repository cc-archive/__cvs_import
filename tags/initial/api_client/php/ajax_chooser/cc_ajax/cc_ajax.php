<?php

/*
 *  $Id$
 *  $Date$
 *  copyright 2005, Nathan R. Yergler, Creative Commons
 *  
 *  Licensed under the GNU GPL v2
 *  see docs/LICENSE for more information
 *
 */

require_once(dirname(__FILE__).'/ccwsclient.php');

// *************************************************************************
// scriptHeader($base='.')
//
// Outputs the javascript <script> tags necessary for the license chooser to
// function properly.  $base specifies the the path to the cc_ajax package.
// If not specified, a relative link is constructed.

function scriptHeader($base='.') {

printf ('
    <script type="text/javascript" src="%s/cc_ajax/behaviour.js"> </script>
    <script type="text/javascript" src="%s/cc_ajax/tw-sack.js"> </script>
    <script type="text/javascript" src="%s/cc_ajax/client.js"> </script>
', $base, $base, $base);

} // scriptHeader

// *************************************************************************
// licenseChooser($action, $prefix)
//
// Renders an HTML license choser.  $action specifies the form action to take
// and $prefix specifies a prefix for the form variable names.

function licenseChooser($action='choose.php', $prefix='') {

printf ('
         <div id="license_selection" class="wrap">
            <form name="license_options" method="post" action="%s">

            <input name="%slicense_name" type="hidden" 
                   value="" />
            <input name="%slicense_uri"  type="hidden"  
                   value="" />
            <input name="%slicense_rdf"  type="hidden"  
                   value="" />
            <input name="%slicense_html" type="hidden"  
                   value="" />

<div id="licenseSelector" name="licenseSelector" class="wrap">
<table>
               <tr><th><nobr>Selected&nbsp;License:</nobr></th>
                   <td id="newlicense_name">(none)</td>
               <td>
               <img id="working" 
                    src="./cc_ajax/Throbber-small.gif" 
                    style="display:none; float:right; margin:0px;padding;0px;"/>
                   </td>
               </tr>
               <tr><th><nobr>License type:</nobr></th>
                 <td colspan="2">
             <select id="%slicenseClass">
                          <option id="-">(none)</option>',
$action, $prefix, $prefix, $prefix, $prefix, $prefix);

    $license_classes = licenseClasses();
    echo $license_classes;
    foreach($license_classes as $key => $l_id) {
          echo '<option value="' . $key . '" >' . $l_id . '</option>';
    }; // for each...

echo '
             </select>
</td></tr>
<tr><td>&nbsp;</td>
<td colspan="2">
         <div id="license_options" class="wrap">
         </div>
</td></tr>
<tr><td colspan="2"><input id="choose" type="submit" value="Choose!"/></td></tr>
</table>
</div>
';

} // licenseChooser

?>