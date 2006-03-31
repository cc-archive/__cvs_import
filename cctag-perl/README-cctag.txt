NAME
    cctag.pl - Tags mp3 and ogg files with license claim URL and generates
    claim RDF.

SYNOPSIS
     cctag.pl <options> <file ...>

     Options:
       --claim_url
       --license_url
       --copyright_year
       --copyright_holder
       --source_url_prefix (optional)

    cctag.pl tags files (currently only mp3 and ogg files) with license
    claim metadata and prints backing license claim RDF to STDOUT.

OPTIONS
    --claim_url
            URL of license claim.

    --license_url
            URL of license for content being tagged.

    --copyright_year
            Year of copyright for content being tagged.

    --copyright_holder
            Name of copyright holder for content being tagged.

    --source_prefix_url
            Optional. If present, a dc:source property is generated,
            composed of the source prefix url and file name.

EXAMPLE
     $ cd /var/www/example.com/html/mp3s
     $ find * -name \*.mp3 | cctag.pl --claim_url http://example.com/licenses1 --license_url http://creativecommons.org/licenses/by/1.0/ --copyright_holder "Example Band" --copyright_year 2003 --source_url_prefix http://example.com/mp3s/ > out.rdf
     $ cat out.rdf
      <!--
        Publish the following RDF at http://example.com/licenses1
      -->
     <rdf:RDF xmlns="http://web.resource.org/cc/"
         xmlns:dc="http://purl.org/dc/elements/1.1/"
         xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
        <Work rdf:about="urn:sha1:MSMBC5VEUDLTC26UT5W7GZBAKZHCY2MD">
           <dc:date>2003</dc:date>
           <dc:format>audio/mpeg</dc:format>
           <dc:identifier>http://example.com/mp3s/test.mp3</dc:identifier>
           <dc:rights><Agent><dc:title>ExampleBand</dc:title></Agent></dc:rights>
           <dc:title>Example Song</dc:title>
           <dc:type rdf:resource="http://purl.org/dc/dcmitype/Sound" />
           <license rdf:resource="http://creativecommons.org/licenses/by/1.0/" />
        </Work>
        <Work rdf:about="urn:sha1:DSPSBIG26ZYXBVSLQA656ETVBLIONRPT">
           <dc:format>audio/mpeg</dc:format>
           <dc:identifier>http://example.com/mp3s/x.mp3</dc:identifier>
           <dc:rights><Agent><dc:title>ExampleBand</dc:title></Agent></dc:rights>
           <dc:type rdf:resource="http://purl.org/dc/dcmitype/Sound" />
           <license rdf:resource="http://creativecommons.org/licenses/by/1.0/" />
        </Work>
        <License rdf:about="http://creativecommons.org/licenses/by/1.0/">
           <requires rdf:resource="http://web.resource.org/cc/Attribution" />
           <permits rdf:resource="http://web.resource.org/cc/Reproduction" />
           <permits rdf:resource="http://web.resource.org/cc/Distribution" />
           <permits rdf:resource="http://web.resource.org/cc/DerivativeWorks" />
           <requires rdf:resource="http://web.resource.org/cc/Notice" />
        </License>
     </rdf:RDF>

PREREQUISITES
    You will probably have to install these:

    Convert::Base32
            http://search.cpan.org/author/MIYAGAWA/Convert-Base32/

    Ogg::Vorbis::Header
            http://search.cpan.org/author/DBP/Ogg-Vorbis-Header-0.03/

    MP3::Tag
            http://search.cpan.org/author/THOGEE/tagged/

            Tagged requires
            http://search.cpan.org/author/PMQS/Compress-Zlib/ which you may
            need to install.

    MP3::ID3Lib
            http://search.cpan.org/author/LBROCARD/MP3-ID3Lib-0.12/

            MP3::ID3Lib requires the C library
            http://id3lib.sourceforge.net.

            Note that in order to compile id3lib on OS X you will need to
            pass a flag to configure:

            ./configure CXX=g++2

    These are probably already installed:

    Digest::SHA1
            http://search.cpan.org/author/GAAS/Digest-SHA1/

    Getopt::Long
            http://search.cpan.org/author/JV/Getopt-Long/

    LWP::UserAgent
            http://search.cpan.org/author/GAAS/libwww-perl/

    Pod::Usage
            http://search.cpan.org/author/MAREKR/PodParser/

SEE ALSO
    http://creativecommons.org/learn/technology/nonweb
            Guidelines for embedding license claims in files.

    http://cctools.sourceforge.net/
            Home page for this tool -- cvs, bugs, etc.

