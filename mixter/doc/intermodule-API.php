<HTML>
<HEAD>
<TITLE>Mixter Documentation</TITLE>
</HEAD>
<BODY>
<P ALIGN="LEFT">
Matthew Drake and Ian Spivey - Team &amp;nbsp;<BR>
<BR>
<FONT SIZE="5">Intermodule API</FONT><BR><BR>
All of the functions needed to generate the content on our different pages will exist within the different modules. Each module will consist of libraries of functions that will be used internally within that module, and templates which will be used within the module. Each module will have a file modulename-interface.php, which will consist solely of the functions that can be called by other modules. Other modules can include this module, and the functions within it will require and call the appropriate functions from the appropriate libraries. With the exception of the "utilities" module, all the modules will return strings containing the HTML code with the appropriate content. This HTML code will have been the product of the library scripts/sub-templates, and the code will be ready to be inserted into a larger document. For instance, a page "view forum" might call one function from the content module, which returns an entire HTML page. A more complicated example would be the administrators "view recent updates," which might call three modules, which would each return a formatted table containing the appropriate data.
<BR><BR>
The utilities module will consist solely of utility functions and variables that many modules might find useful. Examples might include global page layout functions, header functions, and formatted time and dates. Functions in the utilities module will generally not access the database, or if they do, it is in a very content unspecific fashion. (For instance, a function might take all recent database updates and write them to a backup file or transaction log.) For the utilities module, every file will be considered as part of the "intermodule API," since all it really does is define simple helper functions that many modules would want to call.
<BR><BR>
Each of the Users, Group, and Content modules will have a number of functions in their interface file. These will consist of functions that return recent modifications or updates, return contributions by a given user, contributions by a given group, a search function that can search through the tables in each module's domain for an arbitrary search term, return already-deleted contributions, administrative actions (for approving and deleting content, users, or groups), and returning pre-formatted tables that contain data users want to access within a module. (Such as viewing other profile pages / seeing their own, or returning a forum) This will allow forums to appear on group webpages (for that group's personal forum), songs to appear on personal pages, etc. An administrator who wishes to view recent content could go to apage that would call the "get recent contributions" function on each of the categories to see what has happened recently. Likewise, someone could view all of a user's contributions by going to a page that called the "get user contributions" on each of the modules.
<BR><BR>
So far our modules will be:
<UL>
<LI>Users
<LI>Groups
<LI>Content (We may break this up into forums, news/articles, and music, if the code for each of these gets too large, but this is relatively easy to do later as the need arises. The only reason to break it up would be to make it easier for subsequent programmers to locate code quickly)
<LI>Search
<LI>Utilities
</UL>
In the root /www directory will be all of our web scripts, which will correspond to URLs. These will all call the various modules to generate their content. Other scripts that send email reminders, or generate other sorts of content, can also be located here, and can have cron jobs assigned to them.
<P ALIGN="LEFT">
<A HREF="mailto:madrake@mit.edu">madrake@mit.edu</A></P>
</BODY>
</HTML>

