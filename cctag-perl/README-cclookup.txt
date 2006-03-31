NAME
    cclookup.pl - Looks up license claim urls embedded in files and
    corresponds RDF at license claim urls with files in question.

SYNOPSIS
     cclookup.pl [file ...]

    ccllookup.pl looks up license claim urls embedded in files (currently
    only mp3 and ogg files) and corresponds RDF at license claim urls with
    files in question.

EXAMPLE
     $ cclookup.pl example1.mp3 example2.mp3
     Skipping example1.mp3 : failed to get claim url http://example.com/cclicenses/example1
     example2.mp3 LICENSED_UNDER http://creativecommons.org/licenses/publicdomain ACCORDING_TO http://example.com/cclicenses/example2

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

