#!/usr/bin/perl -w

use strict;
use Convert::Base32;
use Getopt::Long;
use LWP::UserAgent;
use MP3::Tag;
use Ogg::Vorbis::Header;
use Pod::Usage;

my $VERSION = '0.4';

my $help = '';

if (!GetOptions(
	"help" => \$help
) || $help || $#ARGV < 0) {
  pod2usage({-exitval => 1});  
}

for my $file_name (@ARGV) {
  my $license_message;
  my $license_urls;
  my $license_claim_url_content;

  if ($file_name =~ /\.mp3$/i) {
    if (!($license_message = cclookup_mp3($file_name))) {
      next;
    }
  } elsif ($file_name =~ /\.ogg$/i) {
    if (!($license_message = cclookup_ogg($file_name))) {
      next;
    }
  } else {
    print STDERR "Skipping $file_name: can't handle file type\n";
    next;
  }
  if (!($license_urls = extract_urls($license_message))) {
    print STDERR "Skipping $file_name : cannot find license and claim url in $license_message\n";
    next;
  }
  my $license_claim_url = $license_urls->{'verify'};
  my $license_url_embedded = $license_urls->{'license'};
  if (!($license_claim_url_content = license_claim_lookup($license_claim_url))) {
    print STDERR "Skipping $file_name : failed to get claim url $license_claim_url\n";
    next;
  }

  my $sha1_32 = sha1_base32($file_name);
       
  if (!($license_claim_url_content =~ /<Work rdf:about="urn:sha1:$sha1_32">.*?<license rdf:resource="(.*?)"\s*\/>.*?<\/Work>/s)) {
    print STDERR "Skipping $file_name : $license_claim_url does not contain a license statement about urn:sha1:$sha1_32\n";
    next;
  }
  my $license_url = $1;
  if (!($license_url eq $license_url_embedded)) {
    print STDERR "Skipping $file_name : $license_url found at $license_claim_url does not match embedded $license_url_embedded\n";
    next;
  }
  print "$file_name LICENSED_UNDER $license_url ACCORDING_TO $license_claim_url\n";
}

sub extract_urls {
  my $license_message = shift;
  if ($license_message =~ /Licensed to the public under (.*?) verify at (.*)$/i) {
    my %license_urls;
    $license_urls{'license'} = $1;
    $license_urls{'verify'} = $2;
    return \%license_urls;
  }
  return 0;
}

sub license_claim_lookup {
  my $license_claim_url = shift;
  my $user_agent = LWP::UserAgent->new;
  $user_agent->agent("cclookup.pl/$VERSION; ".$user_agent->agent);
  my $request = HTTP::Request->new(GET => $license_claim_url);
  my $response = $user_agent->request($request);
  if ($response->is_success) {
    my $content = $response->content;
    return $content;
  }
  return 0;
}


sub cclookup_ogg {
  my $file_name = shift;

  if ($file_name !~ /.*\.ogg$/i) {
    print STDERR "Skipping $file_name : No .ogg\n";
    return 0;
  }

  my $ogg = Ogg::Vorbis::Header->new($file_name);

  if (!$ogg) {
    print STDERR "Skipping $file_name : Not an ogg file?\n";
    return 0;
  }

  foreach my $com ($ogg->comment_tags) {
    if ($com =~ /LICENSE/i) {
      return $_  foreach $ogg->comment($com);
    }
  }

  print STDERR "Skipping $file_name : No LICENSE\n";
  return 0;
}


sub cclookup_mp3 {
  my $file_name = shift;

  if ($file_name !~ /.*\.mp3$/i) {
    print STDERR "Skipping $file_name : No .mp3\n";
    return 0;
  }
  my $mp3 = MP3::Tag->new($file_name);
  if (!defined $mp3) {
    print STDERR "Skipping $file_name : No ID3\n";
    return 0;
  }
  $mp3->get_tags;

  my $v2 = $mp3->{ID3v2};
  if (!$v2) {
    print STDERR "Skipping $file_name : No ID3v2.3\n";
    return 0;
  }

  my $tcop = $v2->get_frame("TCOP");

  if (!$tcop) {
    print STDERR "Skipping $file_name : No TCOP\n";
    return 0;
  }

  return $tcop;
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

exit;

__END__

=head1 NAME

cclookup.pl - Looks up license claim urls embedded in files and corresponds RDF at license claim urls with files in question.

=head1 SYNOPSIS

 cclookup.pl [file ...]

ccllookup.pl looks up license claim urls embedded in files (currently
only mp3 and ogg files) and corresponds RDF at license claim urls with
files in question.

=head1 EXAMPLE

 $ cclookup.pl example1.mp3 example2.mp3
 Skipping example1.mp3 : failed to get claim url http://example.com/cclicenses/example1
 example2.mp3 LICENSED_UNDER http://creativecommons.org/licenses/publicdomain ACCORDING_TO http://example.com/cclicenses/example2


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
