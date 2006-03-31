-- Mixter Content Management Data Model
-- by Matt and Ian
-- Our content will consist of news, articles, and forums. User can also make
-- comments on articles and news.

CREATE TABLE mime_types (
  type_name VARCHAR(100) PRIMARY KEY
);

INSERT INTO mime_types VALUES ('text/plain');
INSERT INTO mime_types VALUES ('text/html');
INSERT INTO mime_types VALUES ('application/octet-stream');
INSERT INTO mime_types VALUES ('audio/mpeg');

CREATE TABLE content_raw (
	content_id              SERIAL PRIMARY KEY,
	-- Tells us whether it is an article, comment, or news
	content_type            VARCHAR(100) NOT NULL
	  CHECK (content_type IN ('article', 'comment', 'news', 'forum_posting', 'forum', 'section', 'picture', 'music')),
	-- if not NULL, this row represents a comment on other content
	-- For forum_postings, if refers_to references a forum, the content
	-- is the start of a thread, and anything else is a response.
	refers_to               INTEGER REFERENCES content_raw (content_id),
	-- if not NULL, this content belongs to a group
	-- If group_owner_id is NULL, the file is associated with the creation artist. If it is not null, it is associated
	--   with the group.
	group_owner             INTEGER REFERENCES user_groups (group_id),
	-- who contributed this and when
	creation_user           VARCHAR(30) NOT NULL REFERENCES users (user_id),
	creation_date           TIMESTAMP NOT NULL,
	release_time            TIMESTAMP,	-- NULL means "immediate"
	expiration_time         TIMESTAMP,	-- NULL means "never expires"
	mime_type               VARCHAR(100) REFERENCES mime_types (type_name),
	viewable_status         VARCHAR(30)
	-- Following fields are only relevant for picture and music
   	  CHECK(viewable_status IN ('public', 'private')),
	-- The name of the song, like "Face in the Sand", or a name for the picture like "Me on the Beach"
	songname                VARCHAR(200),
	-- The name the song will get downloaded as, like "Face in the Sand.mp3"
	filename                VARCHAR(200),
	-- Determines whether anyone can see the file or just the artist/group
	license_url             VARCHAR(250), -- This will contain the appropriate Creative Commons license
	license_name             VARCHAR(250), -- This will contain the appropriate Creative Commons license
        num_downloads		INTEGER,
	-- To find out a song's current rating, total_ratings / num_ratings
	num_ratings		INTEGER,
	total_ratings		INTEGER,
        idxFTI                  tsvector
	sha1_32			character(32),
	copyright_year		integer,
	copyright_holder	character varying(250),
	source_url             VARCHAR(250)
);

CREATE INDEX idxFTI_content_raw_idx ON content_raw USING gist(idxFTI);

CREATE TRIGGER tsvectorupdate_content_raw BEFORE UPDATE OR INSERT ON content_raw
  FOR EACH ROW EXECUTE PROCEDURE tsearch2(idxFTI, songname, filename);

CREATE TABLE content_versions (
	version_id              SERIAL PRIMARY KEY,
	content_id              INTEGER NOT NULL REFERENCES content_raw,
	version_date            TIMESTAMP NOT NULL,
	-- TODO: Make languages reference a table of acceptable lang. codes
	language                CHAR(2),
	-- will hold the title in most cases.
	one_line_summary        VARCHAR(200) NOT NULL,
	-- the entire article; 4 GB limit
	body                    text,
	editorial_status        VARCHAR(30) 
          CHECK (editorial_status IN ('submitted','rejected','approved','expired')),
	-- audit the person who made the last change to editorial status
	editor_id               INTEGER REFERENCES users,
	editorial_status_date   TIMESTAMP,
	current_version_p       CHAR(1) CHECK(current_version_p IN ('t', 'f')),
 	-- Following fields are only relevant for picture and music
	-- The name of the file on the server, like "/var/music/012345-version4.mp3"
	storagename             VARCHAR(300),
	-- a short description of the file
	description             VARCHAR(300),
        idxFTI                  tsvector
);

CREATE INDEX idxFTI_content_versions_idx ON content_versions USING gist(idxFTI);

CREATE TRIGGER tsvectorupdate_content_versions BEFORE UPDATE OR INSERT ON content_versions
  FOR EACH ROW EXECUTE PROCEDURE tsearch2(idxFTI, one_line_summary, body, description);

CREATE INDEX content_versions_current_version_p_index ON content_versions (current_version_p);

-- Put a trigger in for updates/inserts that updates current_version_p

-- Note: We did go ahead with merging the music and content tables, as suggested in class.
--       The previous note here about why we kept them seperate has been removed.

-- This keeps track of which music gets reused in other pieces of music
-- This table only keeps track of one-level of derivative works. I.e.
-- if piece A is used by piece B is used by piece C, you'll need to look through
-- this table twice to find that out.
CREATE TABLE music_mapping (
	music_mapping_id        SERIAL PRIMARY KEY,
	original_music          INTEGER NOT NULL REFERENCES content_raw,
	derivative_music        INTEGER NOT NULL REFERENCES content_raw
);

-- The next three views exist because we enabled picture support, and merged our mp3 and
-- other content tables. These just select out the appropriate content, since most of the time
-- we'll want to deal with them seperately.

-- For when you just want to deal with music
CREATE VIEW music AS SELECT * FROM content_raw
  WHERE content_type='music';

-- For when you just want to deal with pictures
CREATE VIEW pictures AS SELECT * FROM content_raw
  WHERE content_type='picture';

-- For when you want to deal with the rest of the content
CREATE VIEW text_content AS SELECT * FROM content_raw
  WHERE content_type!='music' AND
	content_type!='picture';

CREATE VIEW music_group AS SELECT * FROM music
  WHERE group_owner IS NOT NULL;

CREATE VIEW music_artist AS SELECT * FROM music
  WHERE group_owner IS NULL;

CREATE VIEW live_versions AS SELECT * FROM content_versions 
  WHERE current_version_p = 't';

CREATE TABLE search_queries (
	query_id SERIAL PRIMARY KEY,
	search_date TIMESTAMP NOT NULL,
	query_string VARCHAR(300)
);
