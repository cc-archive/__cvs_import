import libxml2
import libxslt

XSLT_SOURCE='/var/lib/zope/dev/Extensions/chooselicense.xsl'

def chooseLicense(answers):
   """Returns a tuple containing an XML document, html, the license URL and
   the license name."""

   answer_ctxt = libxml2.createPushParser(None, '', 0, 'answer.xml')
   answer_ctxt.parseChunk(answers, len(answers), True)

   # apply the stylesheet
   transform = libxslt.parseStylesheetFile(XSLT_SOURCE)
   result = transform.applyStylesheet(answer_ctxt.doc(), None)

   # get the license info XML
   license_xml = transform.saveResultToString(result)

   # extract the license HTML
   license_doc = libxml2.parseMemory(license_xml, len(license_xml))

   xp_ctxt = license_doc.xpathNewContext()
   name = xp_ctxt.xpathEval('//license-name')[0].content

   xp_ctxt = license_doc.xpathNewContext()
   uri = xp_ctxt.xpathEval('//license-uri')[0].content

   xp_ctxt = license_doc.xpathNewContext()
   rdf = xp_ctxt.xpathEval('//rdf')[0].serialize() # content

   xp_ctxt = license_doc.xpathNewContext()
   licenserdf = xp_ctxt.xpathEval('//licenserdf')[0].serialize()

   xp_ctxt = license_doc.xpathNewContext()
   img_src = xp_ctxt.xpathEval('//html/a/img/@src')[0].content

   return (license_xml, name, uri, img_src, licenserdf)

