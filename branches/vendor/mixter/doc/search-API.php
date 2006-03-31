<HTML>
<HEAD>
<TITLE>Mixter Documentation</TITLE>
</HEAD>
<BODY>
<P ALIGN="LEFT">
Matthew Drake and Ian Spivey - Team &amp;nbsp;<BR>
<BR>
<FONT SIZE="5">Search API</FONT><BR><BR>
The other details of searching and the search module can be found back on the main documentation link. This page details how Search will interact with the rest of the APIs.<BR><BR>
Standards:<BR>
There are three types of searches that can occur: <b>user</b>, <b>song</b>, or <b>information</b> searches. These are not what they will be called for users, but these are the programmer terms. There will be three categories of search results, which correspond to the above searches. Each module will be responsible for returning data in the correct format. The user module will return search results that are in the user results format, and the content module will return information in the song format or the information format, depending on the request.<BR><BR>
Results can be a single result or a set of results. A set of results will be an array of single results. Functions will clearly state which they output. Each single result will be an array with fields depending upon the type of result it is.<BR><BR>
user-results will have the following fields:<BR>
name - Corresponds to the name of the entity returned. (Examples: "someuser", "Iron Maiden")<BR>
description - Corresponds to a description of the entity returned. (Examples: "A user", "A band")<BR>
link - The link the user could click on to view more information related to the relevant entry.<BR>
<BR>
song-results will have the following fields:<BR>
name - Corresponds to the name of the song.<BR>
artist - Corresponds to the song composer.<BR>
description - Corresponds to the description of the song.<BR>
link - The link the user could click on to view more information related to the relevant entry.<BR>
<BR>
information-results will have the following fields:<BR>
name - Corresponds to the name of the content.<BR>
creator - Corresponds to the creator of the content.<BR>
description - A description of the content. (Could be the first couple lines, or a few relevant lines)<BR>
link - The link the user could click on to view more information related to the relevant entry.<BR>
type - Information about the type of content it is, like "message board posting" or "news article."<BR>
<BR>
The search module will take care of displaying search information, but it assumes the data collection is done by the appropriate other modules.
</BODY>
</HTML>

