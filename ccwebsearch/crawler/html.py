
#
# This code taken from a Zope library licensed under the GPL
# http://www.zope.org/Members/mnemonic/engl/python/striphtml
#
# edited slightly by Ben (ben@mit.edu)
#
# GNU GPL 2.0
#
# FIXME: needs to be updated to strip out SCRIPT tags, too
#

import html2text

def strip(s):
    """Removing all HTML from some string"""
    return html2text.html2text(s, basehref="", showlinks=0)

def text_to_html(s):
    """Adds proper newlines and escapes HTML stuff"""

    # Tags
    s= s.replace("&", "&amp;")
    s= s.replace("<","&lt;")
    s= s.replace(">","&gt;")

    # Newlines
    s= s.replace('\r\n', '<br />')
    s= s.replace('\n', '<br />')

    return s
