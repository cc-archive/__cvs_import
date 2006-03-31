// Created by Matthew Drake
// madrake@mit.edu
// Exercise 4 for User Analysis Exercises for 6.171

// Assume the time dimension is created as it exists in the Data Warehousing chapter

create table request_fact (
	fact_id		integer primary key, 
	time_key	integer not null references time_dimension,
	page_request	varchar(400), // The entire GET or POST request
	page_base	varchar(100), // The base name of the page.
	return_code	varchar(100), // Contains a return code if the page gave an error, or null. 
	refered_from	varchar(400),
	session_key	integer not null references session_dimension,
	user_key		integer not null references users(user_id)
	size_of_request	integer, // In bytes.
	content_type	varchar(30) 
		CHECK (content_type IN ('song', 'forum', 'news', 'page', 'search')),
	// content-type is a quick check on what the content is, which is more relevant in some
	// cases than page_base. For instance, a number of pages would be about "forums." A main
	// page, like the home page, would be just a "page" even if it had some forum or song content
	// on it. 
);

create table session_dimension (
	session_id	integer primary key, 
	start_time	integer not null references time_dimension,
	end_time	integer not null references time_dimension,
	duration	integer not null references time_dimension
);

