-------------------------------------
CC Ajax License Chooser Demonstration
-------------------------------------

 :Author: $Author$
 :Version: $Revision$
 :Updated: $Date$

What
====
  This package implements a sample browser client for the Creative Commons 
  license engine web service.  More information on the web services can be
  found at http://api.creativecommons.org.

  The implementation included in this package uses PHP to proxy web service
  requests from your local web host to api.creativecommons.org.  It also uses
  Javascript to implement an AJAX_ interface, like that found in wpLicense_.

How
===
  The cc_ajax package provides both PHP and Javascript files which combine to 
  implement a basic license chooser.  To integrate a license chooser in your
  web-based application:

  #. Modify the WS_ROOT_URL variable in cc_ajax/client.js to point to the 
     location of your copy of ccwsclient.php.  Due to browser security 
     restrictions, this must be in the same domain as your license chooser.

     For example, if your are implementing a license chooser at 
     http://example.com/choose.php with the cc_ajax library installed at the
     root, WS_ROOT_URL would be http://example.com/cc_ajax/ccwsclient.php.
  #. Upload the cc_ajax package to your web server.
  #. Include the chooser in your page by requiring the cc_ajax/cc_ajax.php
     library.  This file defines two functions which are used to generate
     the license chooser:

     #. scriptHeader($base)

        Generates the <script> tags necessary for including the Javascript
        libraries.  In the above example, you would use ::

        <?php scriptHeader("http://example.com"); ?>

     #. licenseChooser($action, $prefix)

        Generates the license chooser itself.  $action specifies the URL to
        post the form to; the package includes choose.php which simply 
        shows the results of the form.  $prefix specifies a string to prefix
        the form variables with.  For example ::

        <?php licenseChooser('choose.php', ''); ?>

  #. The form uses the following variables:

     * ``license_name`` The human-readable name of the license
     * ``license_uri``  The URI of the license deed
     * ``license_rdf``  The RDF block which describes the license permissions,
       prohibitions and requirements.
     * ``license_html`` The HTML block, including the RDF-in-a-comment,
       suitable for embedding in a web page.
     

Acknowledgements
================
  cc_ajax is built using excellent Open Source libraries, including:
   * Simple AJAX Code-Kit (http://twilightuniverse.com/projects/sack/)
   * Behaviour (http://www.ripcord.co.nz/behaviour/)
   * miniXml (http://minixml.psychogenic.com/)

License
=======

  cc_ajax is licensed under the `GNU GPL`_ version 2 or later.  
  See docs/LICENSE
  in the distribution for full license text.  This documentation is 
  licensed under a `Creative Commons Attribution 2.5 License`_.

.. _AJAX: http://en.wikipedia.org/wiki/AJAX
.. _wpLicense: http://yergler.net/projects/wpLicense
.. _`Creative Commons Attribution 2.5 License`: http://creativecommons.org/licenses/by/2.5/
.. _`GNU GPL`: http://www.gnu.org/licenses/gpl.html
