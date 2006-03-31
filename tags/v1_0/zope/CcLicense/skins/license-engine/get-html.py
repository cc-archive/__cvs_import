## Script (Python) "get-html"
##bind container=container
##bind context=context
##bind namespace=
##bind script=script
##bind subpath=traverse_subpath
##parameters=lurl=None
##title=
##
from Products.PythonScripts.standard import html_quote

request = container.REQUEST
RESPONSE =  request.RESPONSE

license_url = lurl or request['license_url']
rdf = context.engine.getLicenseWorkRdf(license_url = license_url,
                                title= (request.has_key('title') and request['title']) or None,
                                creator= (request.has_key('creator') and request['creator']) or None,
                                copyright_holder= (request.has_key('copyright_holder') and request['copyright_holder']) or None,
                                copyright_year= (request.has_key('copyright_year') and request['copyright_year']) or None,
                                description= (request.has_key('description') and request['description']) or None,
                                format= (request.has_key('format') and request['format']) or None,
                                work_url= (request.has_key('work_url') and request['work_url']) or None,
                                source_work_url= (request.has_key('source_work_url') and request['source_work_url']) or None
                               )


html = """
<!-- Creative Commons License -->
<a rel="license" href="%s"><img alt="Creative Commons License" border="0" src="%s" /></a><br />
This work is licensed under a <a rel="license" href="%s">Creative Commons License</a>.
<!-- /Creative Commons License -->


<!--
%s
-->
""" % (license_url, "http://creativecommons.org/images/public/somerights20.gif", license_url, rdf)

print html

return printed
