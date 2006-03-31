## Script (Python) "getQueryString"
##bind container=container
##bind context=context
##bind namespace=
##bind script=script
##bind subpath=traverse_subpath
##parameters=
##title=
##
request = container.REQUEST
RESPONSE =  request.RESPONSE

return "&".join(['%s=%s' % (n, request.form[n]) for n in request.form.keys()])
