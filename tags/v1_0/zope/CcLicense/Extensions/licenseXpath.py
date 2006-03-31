import libxml2
import libxslt

LICENSE_FILE = '/var/lib/zope/dev/Products/CcLicense/Extensions/licenses.xml'

def licenseVersions(code, jurisdiction='-'):
    doc = libxml2.parseFile(LICENSE_FILE)
    ctxt = doc.xpathNewContext() 
    res = ctxt.xpathEval("//license[@id='%s']/jurisdiction[@id='%s']/version/@id" % (code, jurisdiction) )

    return [n.content for n in res]

def latestVersion(code, jurisdiction='-'):
    return max(licenseVersions(code, jurisdiction))

def versionUrl(code, jurisdiction, version):
    doc = libxml2.parseFile(LICENSE_FILE)
    ctxt = doc.xpathNewContext() 
    res = ctxt.xpathEval("//license[@id='%s']/jurisdiction[@id='%s']/version[@id='%s']/@uri" % (code, jurisdiction,version) )

    if len(res) > 0:
       return res[0].content

