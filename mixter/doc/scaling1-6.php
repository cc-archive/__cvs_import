<HTML>
<HEAD>
  <TITLE>Mixter Documentation</TITLE>
</HEAD>
<BODY>
<P ALIGN="LEFT">
Matthew Drake and Ian Spivey - Team &amp;nbsp;<BR>
<BR>
<FONT SIZE="5">Scaling Gracefully Exercises:</FONT><BR><BR>
<B>Exercise 1: Web server-based load balancer</B><BR><BR>

The Zeus Load Balancer is simply handing off requests. It basically acts as a router, and does no actual computation. The 10 web servers on 10 machines have to do a lot of computation because aspects of the abstraction and presentation layer are implemented as part of the web server layer. Therefore, the bottleneck shouldn't slow down computation significantly (assuming the numbers given in the chapter, about a static Zeus server serving static pages is correct). Moreover, it will provide load balancing and failover which would otherwise be lacking.<BR>
<BR>
<B>Exercise 2: New York Times</B><BR><BR>

My system for maintaining the New York Times would be as follows:<BR>

<BR>Editorial Web Server - This server would host all pages related to maintaining and running the New York Times. Editors and those running would connect to this server which would be running under its own IP/Hostname. As there are a limited number of editors, (even assuming 100 editors all working at once), I anticipate a single fast, 4 GHz machine could host this, plus another identical one in parallel as backup.<BR>
Load Balancer - This would have the hostname/IP address for the New York Times and would handle all requests, balancing them among a variety of Content Web Servers. <BR>
Content Web Servers - These would be web servers supplying the actual content for the New York Times, and would all be acting in parallel. Users could get assigned to any by the load balancer, and failover/load balancing would be managed by the load balancer. I would assume that each user would make about ten requests each time they visit. (Serious viewers would read lots of articles, but a lot of people go just for one article.) They might visit every couple days. So if you take total # of users * 10/3 / number of seconds in a day you get the number of 2 Ghz parallel processors you need to throw at the problem of hosting. (I don't know how many users nytimes has, nor did I have a good way of guestimating). <BR>
<BR>
Master Content Database - The master content database would contain all the content that the site could display. This database is both read and writeable. The Editorial Web Server would connect to this server for editors to update and write content in. As there are a limited number of writes to this database it doesn't need to be that fast, as long as it keeps its updates cached, so another 4 Ghz machine should be fine for this, plus another one in parallel as backup.<BR>
Slave Content Databases - There would be many parallel slave content databases. Only the Master Content Database would be allowed write access to the Slaves, and a script running once each minute for each database (although the minutes would be offset between computers for load balancing purposes) on the Master Database would upload the Slave Databases with any new transactions. The Slave Content Databases would be read only with respect to the Content Web Servers, which would only request information from the database. Because they are read only and only updated from one source at the same time, a great number of slave content databases can be run in parallel without concurrency problems. The slave content databases could also be cheap 2 Ghz machines running in parallel. How many you need depends on how many web content servers there are, but in general I would say one for every five to ten web content servers.<BR>
<BR>
Transaction Database - This would be a single database read/writeable by any of the web servers. It would only store transaction information, such as when users log in, what pages they view, and which ads they are shown. This could potentially have to handle a lot of read/write transactions and though each database transaction is incredibly small, there could be a lot of them. <BR>
<BR>
<B>Exercise 3: <BR></B>
<BR>
This system, unlike the New York Times system, will have read access by users on the same order as write access by users, so separating out the databases won't work as well. I would again include a hardware load balancer.<BR>
<BR>
Web Servers - There would be a huge number of parallel web servers processing pages generated from content in the database, and sending them out. These would all be run through a single load balancer. To handle the number of page views necessary (200 million a day) we would need 231 parallel CPUs, assuming ten hits per second but noticing that a lot more hits will happen during peak hours let's get 300 2 GHz parallel servers. These don't have to be anything fancy - Dells would work fine. <BR>
Database - To solve concurrency issues it would be nice to run the database on a single computer. The database would need to handle 200 million page views (assume 5 database accesses per view), 2 million bids (2 million writes), and half a million searches (a whole lot of database accesses per view). This is on the order of 13 thousand database transactions a second. To meet this need I would get an IBM Server Cluster with 128 3 Ghz processors. This would incorporate backup into the Cluster, as individual xSeries servers might go down, but they wouldn't take the whole thing out, and the Cluster server software handles concurrency issues internally. This would mitigate the need for a separate backup system, and this server would be able to handle the massive number of database requests. (Something like this is what eBay actually uses now, I believe.)<BR>
<BR>
<B>Exercise 4:</B> <BR>
<BR>
When bids are placed on items, a maximum bid for that user would be stored along with the bid. Whenever a new bid is placed on an item, or a bid is updated, you compare the new bid/max-bid with the current bid/that person's max bid. This would be something like SELECT (bid info) FROM current_bids_view WHERE bid-item = the item someone just a new bid on, and then INSERT INTO bidtable (bid info) VALUES (new bidding info) Then you compare the new bidding info with the info you selected. Whichever user has the higher bid, you give him the new current bid at one more than the lower bid (ignoring miminum-increment issues, which are easy to get around.) Then you would do two UPDATES on the database to set the new current-bid status to on/off for those two bid items, if it had changed. The code for proxy bidding would operate on the web server, with one or two more database requests per bid transaction. <BR>
<BR>
<B>Exercise 5:</B><BR>
<BR>
It wouldn't significantly change my design. You can just increase the number of web servers running in parallel by five, and increase the number of xSeries servers in the IBM cluster. (You might have to jump up to IBMs next larger cluster size, or possibly one of their high end mainframes might end up being more cost effective)<BR>
<BR>
<B>Exercise 6:</B><BR>
<BR>
Assuming that Hotmail had 10 million users, who check their email five times a day for ten minutes, and click through five pages a minute while they check their email, you would need about 3000 processors running to handle the requests, and then another bunch to run the database. I would suggest a similar separation as for eBay, using the same types of processors/clusters for the web/database parts of the application. <BR>
<BR>
The main difference between Hotmail and eBay is that Hotmail also has to run mail servers and handle a huge body of incoming mail. Incoming mail must be directed into the database, so you will need an enormous amount of processing power on the database. Outgoing mail servers would be fairly minimal to maintain; I had a 386 processor once that was running an outgoing mail server for about 200 people and it didn't have any problems, so assuming scalability, 30 or 40 4 Ghz quad Pentium 4 processors should be enough to grab all the outgoing content from the database and mail it. <BR>
<BR>
<B>Exercise 7:</B><BR>
<BR>
Implement a UNIX-based load balancer at the DNS address www.scorecard.org.  This is done so that a single machine won't get hammered, and we have the flexibility of being able to add new web servers when we need to.  Behind it, put (for the moment) only one or two web servers.  The site has a number of broken links to content (on a page linked from the main page) so I can't imagine it's updated very frequently/sees a ton of traffic.  However, if that assumption is false, it wouldn't be difficult to add more parallel web servers behind the load balancer.  Finally, all of these systems would communicate with a single RDBMS.  This site seems like it sees a lot of queries to the database, but the information in the database is probably updated infrequently (since updates probably occur only when new reports come out, which isn't even close to one hit per second) and the forums don't seem to be used all that much.  Rather than spend a lot of money on a two-node parallel server, one can probably do the job.<BR>
<BR>
<B>Exercise 8:</B><BR>
<BR>
New York Times forums: first of all, they were obnoxiously difficult to find.  They're also not well structured for an individual to ask a question and get an answer, since there are numerous messages posted every day, and they're all just appended to a long long list of posts in that forum.  Even if you scrap the question-and-answer model, the best way to hold a meaningful discussion is to use some sort of threading (any sort of threading, please!) so that users can easily follow the history of a discussion rather than having to sort through thousands of irrelevant posts.  There's also no meaningful information on each user (like what posts he's made, or how many) -- simply a user-made profile, which is just a bunch of text the user can input as he pleases.<BR>
<BR>
At the moment, this system is rather ineffective for an online learning community.  In order to really follow discussion and get much out of them, you have to visit the site at least every day.  There's little identifiability for users, and less accountability.
<BR><BR>
To improve this system, I'd move to a threaded discussion system (juggling recent threads to the top, and perhaps not display old postings in a thread unless a user wants to see them) and make the user-profile page show all the content that user has contributed (so it's easy to make someone accountable regarding what they've said in the past).
<BR><BR>
Making an online community for a newspaper really doesn't have to be a unique challenge.  If the newspaper doesn't want to allow community discussion of its news, or doesn't want to allocate resources for moderation/administration, that's one thing -- but assuming an online newspaper wants to have a discussion forum, it's really no different from plenty of tech-oriented news sites that are already doing this quite well.  Post articles, allow users to discuss these articles in a threaded fashion (either related to each article or in a more general forum).
<BR><BR>
<B>Exercise 9:</B><BR><BR>
When looking at reviews of a product, you can click on a user's name to see all of their reviews, as well as all other content they've contributed to the site.  There's a discussion forum system with many users (albeit few categories and little breadth of discussion).  Users can make lists of products that fall into a certain category, like "Nonfunctionalism and You" (a list of 25 CDs, DVDs, and VHSs that the author likes for some reason or another).  When you view a product, if it's on a person's list, there will be a link to his list (so you can see other products that someone else who might share your tastes likes).  Users can also create "guides", which function on the same principle: if you look at a product and it's featured in a guide (such as "How to snub, best, and impress an Everett Emo Girl"), the product page will include a link to the guide.  Guides feature writing and links to other products.
<BR><BR>
By allowing users to contribute content, Amazon gets free (and convincing) advertising for its products.  If some corporate advertising tells me that because I liked Outkast's new album, I'll probably like XYZ Hip Hop Artist, I'm likely to shrug it off and move on.  But if another user with similar tastes to mine happens to recommend a similar artist, I'm much more likely to check it out and possibly buy it.  And very little cost to Amazon for this whole deal.  Brilliant.  Users feel great when they contribute content and get positive feedback from other users, so they keep contributing.  Amazon keeps getting more high-quality free advertising.
<BR><BR>
<B>Exercise 10:</B><BR><BR>
Our scaling plan is divided into three phases.
<BR><BR>
Phase One:
<BR><BR>
Each user will have a page listing all of their contributions to the site, any biographical information the user chooses to make available, and how long the user has been a member of the site.  This page will be available at the url {site_root}/user?name=$username.  Any occurrence of a username in the site (such as where he has authored content) will be a hyperlink to this page.  This is designed to induce user accountability and identifiability.
<BR><BR>
Phase Two:
<BR><BR>
Users can create "Group Pages" that are intended to correspond to a band or some other sort of musical collaboration group.  Users can ask group leaders to become part of the group, at which point they'll have access to group-specific discussion groups and online music versioning tools.  Their group pages will be linked to the individual pages of the group members, and will link to all their members' musical content.
<BR><BR>
Phase Three:
<BR><BR>
Musical trails will be created between artists when one user "samples" another artist's work.  A connection will then be created between the two indicated that this has happened.  Then users can see who has sampled whose work, and perhaps find new artists they would enjoy.  It'll also induce accountability to artists to provide something new and original based off of old work.
<BR><BR>
Geospatialization will occur through region-specific discussion forums.  The "regional view" of a discussion group will include all of the normal general-view groups' content, but will also have flagged posts that apply only to that certain region.  When a user makes a post, he can flag it as "region-specific".  New replies to region-specific posts will automatically be flagged as region-specific.  Regional content will be organized by groups of zip codes.
<BR><BR>
<P ALIGN="LEFT">
<A HREF="mailto:madrake@mit.edu">madrake@mit.edu</A></P>
</BODY>
</HTML>

