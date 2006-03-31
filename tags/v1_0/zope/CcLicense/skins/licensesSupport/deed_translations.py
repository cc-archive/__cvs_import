## Script (Python) "deed_translations"
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

translations = []

for code in ( 'ca', 'de', 'en', 'es', 'fi', 'fr', 'hr', 'it', 'ja', 'kr', 'nl', 'pt', 'zh_TW', ):
  translations.append({'code':code, 'name':'lang.%s' % code})


return translations
