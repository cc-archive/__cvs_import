## Script (Python) "get-rdf"
##bind container=container
##bind context=context
##bind namespace=
##bind script=script
##bind subpath=traverse_subpath
##parameters=
##title=
##
from Products.PythonScripts.standard import html_quote

request = container.REQUEST
RESPONSE =  request.RESPONSE

rdf = context.engine.getLicenseWorkRdf(license_url = request['license_url'],
                                title= (request.has_key('title') and request['title']) or None,
                                creator= (request.has_key('creator') and request['creator']) or None,
                                copyright_holder= (request.has_key('copyright_holder') and request['copyright_holder']) or None,
                                copyright_year= (request.has_key('copyright_year') and request['copyright_year']) or None,
                                description= (request.has_key('description') and request['description']) or None,
                                format= (request.has_key('format') and request['format']) or None,
                                work_url= (request.has_key('work_url') and request['work_url']) or None,
                                source_work_url= (request.has_key('source_work_url') and request['source_work_url']) or None
                               )

RESPONSE.addHeader('content-type', 'application/rdf+xml')
print rdf
return printed
