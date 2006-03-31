#!/usr/bin/perl -w

use strict;
use Convert::Base32;
use Getopt::Long;
use LWP::UserAgent;
use MP3::ID3Lib;
use MP3::Tag;
use Ogg::Vorbis::Header;
use Pod::Usage;

my $VERSION = '0.4';

my %FIELDS_MP3 = ( 'TIT2' => 'TITLE', 'TYER' => 'DATE' );

my $claim_url;
my $license_url;
my $copyright_year;
my $copyright_holder;
my $source_url_prefix;

if (!GetOptions(
	"claim_url=s" => \$claim_url,
	"license_url=s" => \$license_url,
	"copyright_year=i" => \$copyright_year,
	"copyright_holder=s" => \$copyright_holder,
	"source_url_prefix=s" => \$source_url_prefix
) || !$claim_url
  || !$license_url
  || !$copyright_year
  || !$copyright_holder) {
  my $message;
  if (!$claim_url) { $message .="** claim_url required **\n"; }
  if (!$license_url) { $message .="** license_url required **\n"; }
  if (!$copyright_year) { $message .="** copyright_year required **\n"; }
  if (!$copyright_holder) {$message .="** copyright_holder required **\n"; }
  pod2usage({-msg => $message, -exitval => 1});  
}

my $license_rdf = get_license_rdf($license_url);
if (!$license_rdf) {
  my $message = "** No license RDF at $license_url **\n";
  pod2usage({-msg => $message, -exitval => 2});
}
$license_rdf =~ s/^/   /gm;

my $license_message = "$copyright_year $copyright_holder. Licensed to the public under $license_url verify at $claim_url";

my $out = output_head($claim_url);

my $files_count = 0;

for my $file_name (@ARGV) {
  my $cctag_success;
  my $fields;
  my $format;
  if ($file_name =~ /.*\.mp3$/i) {
    ($cctag_success, $fields) = cctag_mp3($file_name, $license_message);
    $format = "audio/mp3";
  } elsif ($file_name =~ /.*\.ogg$/i) {
    ($cctag_success, $fields) = cctag_ogg($file_name, $license_message);
    $format = "application/ogg";
  }
  if (!$cctag_success) {
    print STDERR "Skipping: $file_name\n";
    next;
  }
  ++$files_count;
  my $sha1_32 = sha1_base32($file_name);
  $out .= output_record($sha1_32,$format,$source_url_prefix,$file_name,$copyright_holder,$fields);
}

$out .= output_foot($license_rdf);

if (!$files_count) {
  my $message = "** No files processed **\n";
  pod2usage({-msg => $message, -exitval => 3});
}

print $out;

exit 0;

sub output_foot {
  my $license_rdf = shift;
  return <<END;
$license_rdf
</rdf:RDF>
END
}

sub output_record {
  my $sha1_32 = shift;
  my $format = shift;
  my $source_url_prefix = shift;
  my $file_name = shift;
  my $copyright_holder = shift;
  my $fields = shift;
  my $out .= <<END;
   <Work rdf:about="urn:sha1:$sha1_32">
END
  if ($fields->{'DATE'}) {
    my $escaped_date = xml_escape($fields->{'DATE'});
    $out .= <<END;
      <dc:date>$escaped_date</dc:date>
END
  }
  $out .= <<END;
      <dc:format>$format</dc:format>
END
  if ($source_url_prefix) {
    my $escaped_source_url_prefix = xml_escape($source_url_prefix);
    my $escaped_file_name = xml_escape($file_name);
    $out .= <<END;
      <dc:identifier>$escaped_source_url_prefix$escaped_file_name</dc:identifier>
END
  }
  my $escaped_copyright_holder = xml_escape($copyright_holder);
$out .= <<END;
      <dc:rights><Agent><dc:title>$escaped_copyright_holder</dc:title></Agent></dc:rights>
END
  if ($fields->{'TITLE'}) {
    my $escaped_title = xml_escape($fields->{'TITLE'});
    $out .= <<END;
      <dc:title>$escaped_title</dc:title>
END
  }
  $out .= <<END;
      <dc:type rdf:resource="http://purl.org/dc/dcmitype/Sound" />
      <license rdf:resource="$license_url" />
   </Work>
END
  return $out;
}

sub output_head {
  my $claim_url = shift;
  return <<END;
<!--
  Publish the following RDF at $claim_url
-->
<rdf:RDF xmlns="http://web.resource.org/cc/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
END
}

sub get_license_rdf {
  my $license_url = shift;
  my $user_agent = LWP::UserAgent->new;
  $user_agent->agent("cctag.pl/$VERSION; ".$user_agent->agent);
  my $request = HTTP::Request->new(GET => $license_url); 
  my $response = $user_agent->request($request);
  if ($response->is_success) {
    my $content = $response->content;
    if ($content =~ /(<License .*<\/License>)/s) {
      return $1;
    }
  }
  return 0;
}

sub cctag_ogg {
  my $file_name = shift;
  my $license_message = shift;

  my %frames = ();

  if ($file_name !~ /.*\.ogg$/i) {
    return 0, \%frames;
  }
  my $ogg = Ogg::Vorbis::Header->new($file_name);
  foreach my $com ($ogg->comment_tags) {
    $frames{uc($com)} = $_ foreach $ogg->comment($com);
  }

  if ($frames{'LICENSE'}) {
    $ogg->edit_comment('license', $license_message);
  } else {
    $ogg->add_comments('license', $license_message);
  }
 
  $ogg->write_vorbis; 

  return 1, \%frames;
}

# MP3::Tag can only deal with id3v2.3 tags.  MP3::ID3Lib does a nice
# job of converting id3v2.0/id3v2.2 tags to id3v2.3, but it doesn't
# seem to modify existing frames.  Workaround: first use MP3::ID3Lib
# to implicitly create/convert to an id3v2.3 tag if not present, then
# use MP3::Tag to set the TCOP frame.  FIXME!!!
sub cctag_mp3 {
  my $file_name = shift;
  my $license_message = shift;

  my %frames;

  if ($file_name !~ /.*\.mp3$/i) {
    return 0, \%frames;
  }

  my $add_frame = 1;

  my $id3 = MP3::ID3Lib->new($file_name);
  foreach my $frame (@{$id3->frames}) {
    my $code = $frame->code;
    my $value = $frame->value;

    $frames{$code} = $value;

    if ($code eq "TCOP") {
      $add_frame = 0;
    }

    if ($FIELDS_MP3{$code}) {
      $frames{$FIELDS_MP3{$code}} = $value;
    }
  }

  if ($add_frame) {
    $id3->add_frame("TCOP", $license_message);
    $id3->commit;
  }
  return cctag_mp3_mp3tag($file_name, $license_message), \%frames;
}

sub cctag_mp3_mp3tag {
  my $file_name = shift;
  my $tcop = shift;

  if ($file_name !~ /.*\.mp3$/i) {
    return 0;
  }
  my $mp3 = MP3::Tag->new($file_name);
  if (!defined $mp3) {
    return 0;
  }
  $mp3->get_tags;

  my $v2 = $mp3->{ID3v2};
  if (!$v2) {
    $mp3->new_tag("ID3v2");
    $v2 = $mp3->{ID3v2};
  }

  if ($v2->get_frame("TCOP")) {
    $v2->remove_frame("TCOP");
  }
  $v2->add_frame("TCOP", $tcop);

  $v2->write_tag;

  return 1;
}

sub xml_escape {
  my $text = shift;
  $text =~ s/&/&amp;/g;
  $text =~ s/</&lt;/g;
  return $text;
}

sub sha1_base32 {
  my $file_name = shift;

  use Digest::SHA1;
  my $ctx = Digest::SHA1->new;

  open(FILE,$file_name);
  $ctx->addfile(*FILE);

  my $sha1_raw = $ctx->digest;
  return uc(encode_base32($sha1_raw));
}


__END__

=head1 NAME

cctag.pl - Tags mp3 and ogg files with license claim URL and generates claim RDF.

=head1 SYNOPSIS

 cctag.pl <options> <file ...>

 Options:
   --claim_url
   --license_url
   --copyright_year
   --copyright_holder
   --source_url_prefix (optional)

cctag.pl tags files (currently only mp3 and ogg files) with license
claim metadata and prints backing license claim RDF to STDOUT.

=head1 OPTIONS

=over 8

=item B<--claim_url>

URL of license claim.

=item B<--license_url>

URL of license for content being tagged.

=item B<--copyright_year>

Year of copyright for content being tagged.

=item B<--copyright_holder>

Name of copyright holder for content being tagged.

=item B<--source_prefix_url>

Optional.  If present, a dc:source property is generated, composed of
the source prefix url and file name.

=back

=head1 EXAMPLE

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

=head1 PREREQUISITES

You will probably have to install these:

=over 8

=item Convert::Base32

L<http://search.cpan.org/author/MIYAGAWA/Convert-Base32/>

=item Ogg::Vorbis::Header

L<http://search.cpan.org/author/DBP/Ogg-Vorbis-Header-0.03/>

=item MP3::Tag

L<http://search.cpan.org/author/THOGEE/tagged/>

Tagged requires L<http://search.cpan.org/author/PMQS/Compress-Zlib/> which
you may need to install.

=item MP3::ID3Lib

L<http://search.cpan.org/author/LBROCARD/MP3-ID3Lib-0.12/>

MP3::ID3Lib requires the C library L<http://id3lib.sourceforge.net>.

Note that in order to compile id3lib on OS X you will need to pass
a flag to configure:

./configure CXX=g++2

=back

These are probably already installed:

=over 8

=item Digest::SHA1

L<http://search.cpan.org/author/GAAS/Digest-SHA1/>

=item Getopt::Long

L<http://search.cpan.org/author/JV/Getopt-Long/>

=item LWP::UserAgent

L<http://search.cpan.org/author/GAAS/libwww-perl/>

=item Pod::Usage

L<http://search.cpan.org/author/MAREKR/PodParser/>

=back

=head1 SEE ALSO

=over 8

=item L<http://creativecommons.org/learn/technology/nonweb>

Guidelines for embedding license claims in files.

=item L<http://cctools.sourceforge.net/>

Home page for this tool -- cvs, bugs, etc.

=back

=cut
