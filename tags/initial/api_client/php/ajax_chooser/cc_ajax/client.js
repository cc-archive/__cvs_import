// $Id$
// $Date$
//
// client-side functions for the CC AJAX license chooser.
//
// copyright 2005, Nathan R. Yergler, Creative Commons
// licensed under the GNU GPL v2.
// see docs/LICENSE for more information.

// WS_ROOT_URL should point to the location of ccwsclient.php on your server
// For example:
// WS_ROOT_URL = 'http://yergler.net/cc_ajax/ccwsclient.php'; 
//
// Note that due to browser security restrictions, this *must* be in the same
// domain your license chooser is served from.

WS_ROOT_URL = 'http://localhost/cc_ajax/ccwsclient.php'; 

// retrieve the list of license classes from the web service 
// and populate the license class drop-down
function loadClasses() {
    ajax = new sack(WS_ROOT_URL);

    function updateUi() {
	// get a handle to the <select> tag
	var select = document.getElementById('licenseClass');

	// create a parser and parse the result
	xdoc = new DOMParser().parseFromString(ajax.response, 'text/xml');

	xp_result = xdoc.evaluate('//license', xdoc, null, 0, null);
	var r_node;

	while ((r_node = xp_result.iterateNext())) {
	    // construct a new <option> element
	    var option = document.createElement('option');
	    option.setAttribute('value', r_node.getAttribute('id'));
	    
	    option.appendChild(
		     document.createTextNode(r_node.firstChild.nodeValue));

	    select.appendChild(option);
	} // while more results...

    } // updateUi

  ajax.element='';
  ajax.onCompletion = updateUi;
  ajax.runAJAX();

} // loadClasses


function showSelector(event) {
   // clear the questions div, just in case
   document.getElementById('license_options').innerHTML = '';

   // show the license class selector
   document.getElementById('licenseSelector').style.display="block";
   event.cancelBubble = true;

   return true;
} // showSelector

function cancelChanges() {
   // new Effect.Fade('licenseSelector');
   document.getElementById('licenseSelector').style.display="none";

   document.getElementById('working').style.display="none";
   document.getElementById('newlicense_name').innerHTML = "";

   document.license_options.reset();
   return 1;
} // cancelChanges

function showWorking() {
   document.getElementById('working').style.display="block";
   document.getElementById('choose').disabled = true;
} // showWorking

function hideWorking() {
   document.getElementById('working').style.display="none";
   document.getElementById('choose').disabled = false;
}

function retrieveQuestions() {
  cmbLC = document.getElementById("licenseClass");

  showWorking();

  ajax = new sack(WS_ROOT_URL);
  ajax.element='license_options';
  ajax.setVar('func', 'questions');
  ajax.setVar('class', cmbLC.value);
  // ajax.onCompletion=function() {alert (ajax.response);}
  ajax.runAJAX();

  setTimeout('updateLicense()', 2000);
  setTimeout('Behaviour.apply()', 3000);
} // retrieveQuestions

function updateLicense() {
  lic_opts = document.getElementById("license_options");

  // collect the question answers into an array
  var answers = new Array();
  var input_fields = document.getElementsByTagName("select");

  showWorking();

  i = 0;
  for (i = 0; i < input_fields.length; i++) {
        // see if this is a license question
        if (!input_fields.item(i).hasAttribute("lic_q")) {continue; }
        answers[answers.length] = input_fields.item(i).getAttribute("id") + ":" +  input_fields.item(i).value;

  } // for each child node

  answers = answers.join();

  // call the server side license issue function
  ajax = new sack(WS_ROOT_URL);
  ajax.onCompletion = function() {hideWorking(); 
                                  updateLicense_cb(ajax.response); };
  ajax.setVar('func', 'issue');
  ajax.setVar('class', document.getElementById("licenseClass").value);
  ajax.setVar('answers', answers);
  
  ajax.runAJAX();

} // updateLicense

function updateLicense_cb(result) {
   // explode the returned string back into an array
   var licenseInfo = new Array();
   pairs = result.split(';');

   for (i=0; i < pairs.length; i++) {
      item_info = pairs[i].split(':');
      key = item_info.shift();
      licenseInfo[key] = item_info.join(':');
   } // for each pair


   // update the form
   document.license_options.license_name.value = licenseInfo['name'];
   document.license_options.license_uri.value  = licenseInfo['uri'];
   document.license_options.license_rdf.value  = licenseInfo['rdf'];
   document.license_options.license_html.value = licenseInfo['html'];

   // construct and assign the html link
   href_text = '<a href="' + licenseInfo['uri'] + '">' + licenseInfo['name'] + '</a>';
   document.getElementById("newlicense_name").innerHTML = href_text;

} // updateLicense_cb


/* Register Javascript Rules */
var adminRules = {
        '#licenseClass' : function (el) {
             el.onchange = function() {
                retrieveQuestions();
             } // onchange
        }, // licenseClass
        '#cancel' : function (el) {
             el.onclick = function() {
                cancelChanges();
             } // onclick
        }, // #cancel
        '#license_options select' : function (el) {
             el.onchange = function() {
                updateLicense();
             } // onchange
        }, // #lic_questions select
        'select.lic_q' : function (el) {
             el.onchange = function() {
                updateLicense();
             } // onchange
        }, // #lic_questions select
     };

Behaviour.register(adminRules);

