<HTML>
<HEAD>
<TITLE>Mixter Documentation</TITLE>
</HEAD>
<BODY>
<P ALIGN="LEFT">
Matthew Drake and Ian Spivey - Team &amp;nbsp;<BR>
<BR>
<FONT SIZE="5">Development Standards</FONT><BR><BR>
<FONT SIZE="4">Technology:</FONT><BR>
<P>
The RDBMS backend is PostgreSQL, currently version 7.3.4 (Gentoo Linux ebuild).
Procedural code in the RDBMS is written in PL/pgSQL.
Web scripts are written in PHP (version 4.3.2 compliant, as of this document).
Database connections are made through PHP's library of PostgreSQL functions.
</P>

<FONT SIZE="4">Variables and Functions:</FONT><BR>
<P>
Variables are named in a detailed fashion -- much better to have variables with
long, self-explanatory names than with short and useless names.  Multiple-word
variables represent spaces with underscores (no concatenation or dashes).
</P>
<P>
Function naming follows the same convention.  Functions which are used 
internally (in only one file) should be prefixed with "internal_", and every 
function that is not valuable for use outside of a file should be 
designated "internal".
</P>
<P>
All functions should be preceded by comments detailing "Pre:" and "Post:" 
conditions.  The "Pre" description should include a description of all the 
variables the function takes and any preconditions on them (what type they
are, if they've been preprocessed in some way) as well as more general 
preconditions (e.g. the database connection is open, headers have not been
sent).  The "Post" description should describe what value is returned, as well
as what is modified during the course of the function, and how it is 
modified -- essentially, how the state of the application is changed by 
running the function.
</P>
<FONT SIZE="4">URLs:</FONT><BR>
<P>
URLs will be addressed abstractly in code (ie, without extensions).  Multi-word
filenames will be delimited with dashes (no underscores, no concatenation).  
Filenames should also not be abbreviated, with the exception of the word 
"admin" and "config".  In the case of multi-page documents (such as forms), 
URLs should stay the same from page to page (with the possible exception of 
HTTP GET params).
</P>
<FONT SIZE="4">Forms & Input-checking:</FONT><BR>
<P>
Form validation and processing will be done in a seperate intermediate page 
(neither the source nor the 
destination page), and this page will be simply named the name of the source 
page with a "-submit" suffix.  For example, create-user posts its data to 
create-user-submit, which then forwards the user to the desired destination 
(even if the source and destination are the same).
</P>
<P>
Input validation is performed in such intermediate files.  There are library 
functions that perform input-checking (to avoid putting malicious code in 
the database) for strictly-regulated inputs (such as usernames) as well as 
content input (such as the body of a news article) which are treated 
differently with respect to stripping tags.
</P>
<FONT SIZE="4">Error-handling:</FONT></BR>
<P>
The "error.php" file is a library of functions for displaying errors, and for
allowing errors to be passed to any page.  An HTTP GET param of "error=foo" will
cause any page using the "error.php" library to output the error "foo" in a 
standard format defined in that library.  This library should be included in all
pages, and should be the only method of error reporting from form processing.
</P>
<FONT SIZE="4">Configuration Variables:</FONT><BR>
<P>
Such variables are stored as Apache server variables, accessible from any 
web script.  These variables are stored in the "globals" file, and adding
a new variable is as simple adding a new assignment operator to the file and
reloading the server.  Variables associated with an individual module are
prefixed with "module-$modulename-", where $modulename is the name of the 
associated module.
</P>
<FONT SIZE="4">Templating:</FONT><BR>
<P>
Scripts that correspond to visible, navigable URLs (like view-music, and unlike
error.php) have corresponding template files, which have the suffix of
".template" (so view-music's template would be view-music.template).  Each 
visible script should require its template, and then after the script has 
finished it calls a display function of its template which is then evaluated
in the context of the script (ie, all of its variables maintain their state).
Then the template turns the variable values from the script into some sort of 
HTML, such as a table.  Finally, the template calls its corresponding 
master template, which takes the HTML output of the first templates and 
plugs it into appropriate spots.  view-music and view-article, for example, 
might share a master-view-content.template.
</P>
<FONT SIZE="4">Documentation:</FONT><BR>
<P>
Each module has a documentation file in the doc/ directory which is named
"module-" suffixed by the name of the module, e.g. module-users.  This 
doc file contains a list of all the files contained in the module and their
purpose, as well as a high-level description of the operation of the module in
terms of data, flow, and administration.  Finally, it should include a list
of all non-internal procedures in the module, both stored and in scripts, as 
well as relevant Pre: and Post: documentation.
</P>
<P>
Web scripts should begin with comments describing the author, the name of the 
file, and its purpose in some detail (including the names of useful 
externally-available methods, if applicable).
</P>
<P>
Shared procedures should be documented in the same way as functions described
above, with Pre and Post conditions.</P>
<BR><BR>
<P ALIGN="LEFT">
<A HREF="mailto:madrake@mit.edu">madrake@mit.edu</A></P>
</BODY>
</HTML>

