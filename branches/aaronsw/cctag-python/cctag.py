"""cctag: tag works with Creative Commons metadata"""
import eyeD3, sha, base32, urllib

def genID3(files, claim, license, year, holder, source=None):
	newfs = []
	for f in files:
		t = eyeD3.Tag()
		t.link(f)
		
		newf = {}
		newf['name'] = f
		newf['title'] = t.getTitle()
		newfs.append(newf)
		
		ltext = year + ' ' + holder + '. Licensed to the public under ' + license + ' verify at ' + claim
		t.frames.removeFramesByID('TCOP'); t.update()
		t.frames.setTextFrame('TCOP', ltext, '\x03') # utf-8
		t.update()
	
	return newfs

def genRDF(files, claim, license, year, holder, source=None):
	out = ''
	out +=  "<!-- Publish this file at " + claim + " -->\n"
	out += """<rdf:RDF xmlns="http://web.resource.org/cc/"
  xmlns:dc="http://purl.org/dc/elements/1.1/"
  xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">\n"""
	
	for f in files:
		h = base32.b2a(sha.new(open(f['name']).read()).digest()).upper()
		out += '<Work rdf:about="urn:sha:'+h+'">\n'
		out += '\t<dc:date>' + year + '</dc:date>\n'
		out += '\t<dc:format>audio/mpeg</dc:format>\n'
		if source:
			out += '\t<dc:identifier>'+source+'</dc:identifier>\n'
		out += '\t<dc:rights><Agent><dc:title>'+holder+'</dc:title></Agent></dc:rights>\n'
		out += '\t<dc:title>'+f['title'] + '</dc:title>\n'
		out += '\t<dc:type rdf:resource="http://purl.org/dc/dcmitype/Sound" />\n'
		out += '\t<license rdf:resource="'+license+'" />\n'
		out += '</Work>\n'
	
	try:
		lre = re.compile("<License.*?</License>", re.DOTALL)
		out += lre.findall(urllib.urlopen(license).read())[0] + '\n'
	except:
		pass 
	
	out += '</rdf:RDF>\n'
	
	return out

if __name__ == '__main__':
	import sys
	
	usage = "Usage: ./cctag claimURL license copyrightYear copyrightHolder [--url URL] [mp3s]"
	try:	
		(claim, license, year, holder) = sys.argv[1:5]
		if sys.argv[5] == "--url":
			source = sys.argv[6]
			files = sys.argv[7:]
		else:
			source = None
			files = sys.argv[5:]
	except:
		print __doc__
		print usage
	else:	
		files = genID3(files, claim, license, year, holder, source)
		print genRDF(files, claim, license, year, holder, source),
