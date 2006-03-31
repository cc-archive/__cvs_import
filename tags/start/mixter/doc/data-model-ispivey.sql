-- Data Model for Mixter Database Software

-- All data input into the table should be safe to output -- ie it should
-- be stripped of tags and add-slash'd before being INSERTed.

CREATE TABLE users (
	user_id		serial,
	user_name	varchar(30),
	password	varchar(100),	-- we'll store the password MD5-encrypted
	given_name	varchar(30),
	family_name	varchar(30),
	email		varchar(100),   -- we decided to keep track of usernames and email
                                        -- seperately. I for one, don't want my username to always
                                        -- be my email.
	phone_number	varchar(30),
	address_line_1	varchar(100),
	address_line_2	varchar(100),
	address_city	varchar(100),
	address_state	varchar(30),
	address_postal_code	varchar(20),
	address_country_code	varchar(2),
	date_joined	date,
        time_joined     time,
	gender		char
          CHECK (gender IN ('M', 'F')),
	banned		boolean default false,
	role		VARCHAR(30)
	  CHECK (role IN ('user', 'admin', 'superadmin')),
	PRIMARY KEY(user_id),
	UNIQUE (user_name)
);

GRANT ALL ON users TO mixter;

-- Index automatically created on user_name since it's unique

CREATE INDEX users_email_index ON users (lower(email));

CREATE TABLE user_groups (
	group_id		serial,
	group_name		varchar(50),
	group_description 	varchar(300),
	PRIMARY KEY (group_id),
	UNIQUE (group_name)
);

GRANT ALL ON user_groups TO mixter;

-- Index automatically created on group_name since it's unique.

CREATE TABLE user_group_mapping (
	mapping_id	serial,
	user_id		integer,
	group_id	integer,
	mapping_permission	varchar(20)
          CHECK (mapping_permission IN ('user', 'owner')),
	PRIMARY KEY (mapping_id),
	UNIQUE (user_id, group_id)
);

CREATE INDEX user_group_mapping_user_id_index ON user_group_mapping (user_id);

CREATE INDEX user_group_mapping_group_id_index ON user_group_mapping (group_id);

-- not sure if we should index on group_id, user_id or not... it might help
-- when hitting a group page that lists a bunch of its members (probably on
-- each main page)

GRANT ALL ON user_group_mapping TO mixter;
