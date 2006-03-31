"""cclookup: verify MP3 metadata"""
import eyeD3, urllib, sha, re, base32

def getData(filename):
	results = {}
	
	tag = eyeD3.Tag()
	tag.link(filename)
	tcop = tag.frames['TCOP'][0].text
	
	vtext = 'verify at '
	vloc = tcop.find(vtext)
	if vloc != -1:
		results['verify at'] = tcop[vloc+len(vtext):].strip()
		tcop = tcop[:vloc]
	
	ltext = "licensed to the public under "
	lloc = tcop.lower().find(ltext)
	if lloc != -1:
		results['license'] = tcop[lloc+len(ltext):].strip()
		tcop = tcop[:lloc]

	results['copyright'] = tcop.strip()
	
	results['sha1'] = base32.b2a(sha.new(open(filename).read()).digest()).upper()
	
	return results

def verifyData(verifyat, sha1, license):
	vdata = urllib.urlopen(verifyat).read()
	vre = re.compile('.*<Work rdf:about="urn:sha1:'+sha1+'">.*?<license rdf:resource="'+ license + '"\s*\/>.*?<\/Work>.*', re.DOTALL)
	return vre.match(vdata)

if __name__ == '__main__':
	import sys
	if not sys.argv[1:]: 
		print __doc__
		print "Usage: ./cclookup [files]"
	for fn in sys.argv[1:]:
		print 'validating', fn+':',
		try: results = getData(fn)
		except:
			print 'FAILED - file invalid.'
			continue
		
		if not results.has_key('license'):
			print 'no license.'
			continue
		
		print 'licensed under', results['license'],
		
		try:
			valid = verifyData(results['verify at'], results['sha1'], results['license'])
		except:
			print "but can't verify."
			continue
		
		if valid: print "verified at", results['verify at']
		else: print "but can't verify."
