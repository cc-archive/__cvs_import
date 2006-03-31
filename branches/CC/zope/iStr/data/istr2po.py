#!/usr/local/bin/python

# istr2po.py [directory ...]
# dumps corresponding .po files in current dir

import sys, os, stat, re

for dir in sys.argv:
    mode = os.stat(dir)[stat.ST_MODE]
    if (stat.S_ISDIR(mode)): 
        files = os.listdir(dir)
        files.sort()
        buf = ''
        for fname in files:
            if (fname[-5:] != '.html'):
                continue
            f = file(dir+'/'+fname)
            s = f.read()
            s = re.sub(r'"',r'\\"',s)
            s = re.sub(r'\r\n',r'\n"',s)
            s = re.sub(r'\s+$',r'',s)
            s = re.sub(r'^\s+',r'',s)
            s = re.sub(r'\n',r'\\n',s)
            s = re.sub(r'@(\S+?)@',r'%(\1)s',s)
            msgid = fname[:-5]
            buf += 'msgid "'+msgid+'"\n'
            buf += 'msgstr "'+s+'"\n'
            buf += '\n'
        pofile = file('icommons-'+dir+'.po','w+')
        pofile.write(buf)
