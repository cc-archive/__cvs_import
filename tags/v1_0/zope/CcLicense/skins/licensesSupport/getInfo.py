## Script (Python) "getInfo"
##bind container=container
##bind context=context
##bind namespace=
##bind script=script
##bind subpath=traverse_subpath
##parameters=
##title=
##
from Products.PlacelessTranslationService import translate
from Products.PythonScripts.standard import html_quote,urlencode

request = container.REQUEST
RESPONSE =  request.RESPONSE

attrs = []

for lic in request['license_code'].split('-'):
    
    # Go through the chars and build up the HTML and such
    char_title = translate (domain='icommons', msgid='char.%s_title' % lic, as_unicode=True)
    char_brief = translate (domain='icommons', msgid='char.%s_brief' % lic, as_unicode=True)

    icon_name = 'deed'

    if lic == 'nc':
      if request['jurisdiction'] == 'jp':
         icon_name = '%s-jp' % icon_name
      elif request['jurisdiction'] in ('fr', 'es', 'nl', 'at', 'fi', 'be', 'it'):
         icon_name = '%s-eu' % icon_name
    elif lic == 'by' and request['jurisdiction'] == 'br':
      icon_name = '%s-br' % icon_name

    attrs.append({'char_title':char_title,
                  'char_brief':char_brief,
                  'icon_name':icon_name,
                  'char_code':lic,
                 })

country = (request['jurisdiction'] and translate(domain='icommons', msgid='country.%s' % request['jurisdiction'])) or ''

if (request['jurisdiction'] == 'es'):
   langs = "<a href=\"legalcode.ca\">%s</a> <a href=\"legalcode.es\">%s</a>" % (
           translate (domain='icommons', msgid='lang.ca'), translate (domain='icommons', msgid='lang.es'))
elif (request['jurisdiction'] == 'ca'):
   langs = "<a href=\"legalcode.en\">%s</a> <a href=\"legalcode.fr\">%s</a>" % (
           translate (domain='icommons', msgid='lang.en'), translate (domain='icommons', msgid='lang.fr'))
elif (request['jurisdiction'] == 'be'):
   langs = "<a href=\"legalcode.fr\">%s</a> <a href=\"legalcode.nl\">%s</a>" % (
           translate (domain='icommons', msgid='lang.fr'), translate (domain='icommons', msgid='lang.nl'))
else:
   langs = None

# translate(domain='icommons', msgid='licenses.pretty_%s' % request['license_code'].strip()),
return {'pretty_name': translate(domain='icommons', msgid='licenses.pretty_%s' % request['license_code'].strip()),
        'country_name': country,
        'multilanguages': langs,
        'attributes':attrs
       }
