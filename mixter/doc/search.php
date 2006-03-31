<HTML>
<HEAD>
  <TITLE>Mixter Documentation</TITLE>
</HEAD>
<BODY>
<H1>Exercise 1: Expected Queries</H1>
Based on phone conversations with our client, we thought the following searches might be likely.
(Note: Neeru is going to get back to us by Tuesday or Wednesday with a more thorough list)<BR>
(Update: Neeru got back to us. At the bottom of this section is her response)<BR>
<UL>
 <LI>Songs by an artist (Bob wants to hear songs by Bruce Dickinson)
 <LI>Songs by a group (Bob wants to hear songs by Iron Maiden)
 <LI>types of songs by genre (Bob wants to hear heavy metal songs)
 <LI>Songs by name (Bob remembers hearing some song with the phrase "die with your boots on" and wonders if that might be the song title)
 <LI>Songs that sample a particular song (Bob really likes techno and wants to know if anyone has done techno remixes of his favorite heavy metal song "Rainmaker")
 <LI>Artists by location (Bob is looking to start a band and wonders if there are any drummers in Boston)
 <LI>Artists by genre (Bob wants to start listening to some new heavy metal bands that sound like Iced Earth)
 <LI>Users may have questions regarding music production, publishing, and licensing (asked in the forums, but users may search to see if the answer already exists. This information will likely be in the articles or message boards.) (Bob wants to know what the process is for getting a CD made and published under a major label.)
</UL>
Creative Commons would like to embed meta-data into the songs that get uploaded. We don't yet have an exact specification of what that meta data is, but they'd like all of that information to be searchable.
<BR><BR>
Neeru's suggestions:<BR>
<I>
Search for Users by:<BR>
<BR>
First Name<BR>
Last Name<BR>
Username<BR>
Musical Interests<BR>
Instrument<BR>
Names of songs<BR>
Favorite Group<BR>
What they're looking for<BR>
Other users who've linked to<BR>
Date joined<BR>
<BR>
Search for Song by:<BR>
<BR>
Name<BR>
All remixes of one song<BR>
All authors of those remixes<BR>
Genre<BR>
Date song added<BR>
Requested pieces for the song (by the owner)<BR>
Version <BR>
<BR>
<BR>
Also maybe search for active collaborations using a collaboration space? The forum space looked okay to me.  Is that going to be it's own page?</I>
<H1>Exercise 2: Search Design</H2>
The various information that users will want to search by is in the content and user tables of the database. Whatever meta data should be in the songs - we will add equivalent fields to the content tables, so we don't have to worry about building a full-text search on filesystem files. 
<BR><BR>
To search, we will use the tsearch2 module for PostgreSQL. This is a module that compiles and plugs into postgresql. To use it, we create additional fields in the tables, which have a special type and are updated by the tsearch2 module. These tables contain the full text index, and can be used to select out rows, based on how well they match text phrases. There is also an "ispell" module which will compare different forms of words in a known dictionary. 
<BR><BR>
The data we will want to build full text indexes on:
<UL>
<LI>In the users table: user_name, given_name, family_name
<LI>In the user_groups table: group_name, group_description
<LI>In the content_raw table: songname, filename
<LI>In the content_versions table: one_line_summary, body, description
</UL>
<BR>
We will have three seperate searches...
<UL>
<LI>There will be a 'search for musicians,' which will compile results from the users table indexes and user_groups table indexes into one cohesive page.
<LI>There will be a 'search for music,' which will compile results from the content_raw: songname and filename indexes, and the content_Versions: one_line_summary and description indexes, but only for content that is a music file. This search will incorporate tsearch2's lexical analysis tools to search for different forms of words.
<LI>There will be a 'search for information,' which will compile results from the content_versions: one_line_summary, body, and description full text indexes, for all content which is news, articles, forum postings, or forums themselves. This search will incorporate tsearch2's lexical analysis tools tos earch for different forms of words.
</UL>
The way this will be implemented for a user interface: we will have a single search box, and our search results page will have three seperate sections, each displaying the relevant search as listed above. This will be the best way to implement search because (1) a single unified site wide search isn't that useful, because the user is probably only looking for one of the above three at a time, but (2) there may be overlap, i.e. a user may want information about an artist - not just that artist's personal pages, but listener responses in the forums. We feel this unified-search would give the user the most information, but still help subdivide it as it would be useful for them.
<BR><BR>
With the appropriate triggers added, the full text indexing should keep itself updated as information is inserted and updated. (These triggers are mentioned in the intro to tsearch2 that Ben sent out) We will add these triggers to the index fields to keep them updated. Based (as I understand it) on the way tsearch2 indexes, we will keep the database stored efficiently by setting a cron job to run "vacuum full analyze" on the database once a week. If search starts lagging too much towards the end of the week under a heavy load, we can increase the frequency of the cron job.
<BR><BR>
In terms of the pages - there will be a search-submit page in the main web folder which will process all the search requests and display the results. Each page will have a search box located somewhere (either at the top, or the bottom of the page, or both), and all of these search boxes will call the general purpose search page. There will also be a seperate page just called search which is solely for searching, and will also explain the advanced searching options.
Other Notes:
We will want to be able to determine which songs remix which other songs. This will be handled by currently existing tables in the database, and while it's a search, it doesn't require any of the full-text search indexing. It will have its own page and normal SQL queries to handle it.
<BR><BR>
We may also want people to search for artists by location, but again; this wouldn't be handled by the full text search. This would be a straight script/database lookup.
<BR><BR>
To help administrators and editors, we will keep track of all the search queries asked. However, we don't believe it would be ethical to associate them with users. As such, we will have a table that stores search results and date searched, but that's it. Administrators will be able to see the sites popular search terms.
<A HREF="mailto:madrake@mit.edu">madrake@mit.edu</A></P>
</BODY>
</HTML>
