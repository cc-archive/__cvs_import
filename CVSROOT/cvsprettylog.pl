#!/usr/bin/perl -t
#
# cvsmailer.pl
#
# This script is for formatting cvs log with url to the webcvs for ppl to
# see changes.
#
# Copyright (C) 2006 Creative Commons
#
# This file is made available under a CC-GNU-GPL license:
# http://creativecommons.org/licenses/GPL/2.0/
# 

use strict;

use constant PRINT_LOG => 1; # true or false to print log
use constant PRINT_ORIGINAL => 0; # true or false to print original message
# the standard url to view webcvs
use constant WEBCVS => 'http://cvs.sourceforge.net/viewcvs.py';

my $cvs_path; # path extracted to cvs module
my @files;    # array of all modified files
my $msg;      # storage variable for log message

my $buf_files_enabled = 0;    # true for recording to this var
my $buf_msg_enabled = 0;      # true for recording to this var

# iterate through stdin (piped in)
while ( my $line = <STDIN> ) {
    chomp $line;

    ($cvs_path = $1) if ( $line =~ /^Update of \/cvsroot\/(.*)/ );

    (print "$line\n") if ( PRINT_ORIGINAL );

    # turn on storing of log messages after seeing this
    if ( $line =~ /^Log Message:/ ) {
        $buf_files_enabled = 0;
	$buf_msg_enabled = 1;
	next;
    }

    # store files changed
    if ( $buf_files_enabled ) {
        $line =~ s/ *(\w)/$1/;
        push( @files, $line );
    }

    # store log messages if enabled
    if ( PRINT_LOG && $buf_msg_enabled ) {
        $msg .= "$line\n";
    }

    # if see this regex, then start storing of files
    if ( $line =~ /^Modified Files:/ ) {
        $buf_files_enabled = 1;
    }
}

# The following is for the newly formatted output.

(print "\n") if ( PRINT_ORIGINAL );

print "Modified Files (NOTE: WEBCVS is up to 24 hours behind CVS): \n";
foreach my $file ( @files ) {
    print "$file " . WEBCVS . "/$cvs_path/$file\n";
}

if ( PRINT_LOG ) {
    print "\nLog Message:\n";
    print "$msg\n";
}
