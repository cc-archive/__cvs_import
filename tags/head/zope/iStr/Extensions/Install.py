from Products.iStr.config import PROJECTNAME
from Products.Archetypes.public import listTypes
from Products.Archetypes.Extensions.utils import installTypes
from StringIO import StringIO

def install(self):
    out = StringIO()

    installTypes(self, out,
                 listTypes(PROJECTNAME),
                 PROJECTNAME)

    print >> out, "Successfully installed %s." % PROJECTNAME
    return out.getvalue()

