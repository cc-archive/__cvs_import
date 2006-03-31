<HTML>
<HEAD>
<TITLE>Mixter Documentation</TITLE>
</HEAD>
<BODY>
<P ALIGN="LEFT">
Matthew Drake and Ian Spivey - Team &amp;nbsp;<BR>
<BR>
<FONT SIZE="5">Version Control:</FONT><BR><BR>
<B>What is your system for versioning content?</B><BR>
We currently use a Third Normal Form content representation to encapsulate
content versioning, a la the Content Management section in the textbook.
<BR><BR>
<B>What is your system for versioning the software behind your application,
including the data model and page scripts?</B><BR>
We use a CVS repository contained on the development server to version our
source code (data model and page scripts).  There is a seperate development
and production environment, but these are essentially different directories and
not seperate HTTP servers.  Since the development and testing teams are
essentially one and the same, it makes little sense to follow the three-server
model.  The two-server model allows us to make sure the site that is visible to
the public is always almost entirely working (and a bug submission system
developed with the discussion system will allow us to track down anything that
isn't working in the production version).  The development site can be up and 
down depending on what the current status of our development is, although 
code will never be committed to CVS if we know it breaks a page.
<BR><BR>
<B>What kind of answer can your system produce to the question "Who is
responsible for the content on this current user-visible page?"</B><BR>
Each piece of content has a creator who initially created it and a list of 
editors who have edited it, ordered by date.  Administrators are the other 
people who are "responsible" for content, as they can approve or reject content.
Administrators can also be easily selected from the RDBMS.
<BR><BR>
<P ALIGN="LEFT">
<A HREF="mailto:madrake@mit.edu">madrake@mit.edu</A></P>
</BODY>
</HTML>  
