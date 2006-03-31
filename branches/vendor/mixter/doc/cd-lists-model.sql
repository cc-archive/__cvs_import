CREATE TABLE cd_lists (
	cd_list_id	SERIAL PRIMARY KEY,
	name		VARCHAR(200)
);

CREATE TABLE cds (
	asin		VARCHAR(50) PRIMARY KEY,
	title		VARCHAR(200),
	artists		VARCHAR(200),
	image_url_small	VARCHAR(200),
	image_url_medium	VARCHAR(200),
	price		VARCHAR(20),
	amazon_url	VARCHAR(200),
	product_description VARCHAR(600)
);

CREATE TABLE user_cd_list_mappings (
	user_cd_list_mapping_id		SERIAL PRIMARY KEY,
	user_id				INTEGER REFERENCES(users),
	cd_list_id			INTEGER REFERENCES(cd_lists)

);

CREATE INDEX user_cd_list_mappings_user_id_idx ON user_cd_list_mappings (user_id);

CREATE TABLE cd_list_cd_mappings (
	cd_list_cd_mapping_id		SERIAL PRIMARY KEY,
	cd_list_id			INTEGER REFERENCES(cd_lists),
	cd_id				VARCHAR(50) REFERENCES (cds)
);

CREATE INDEX cd_list_cd_mappings_cd_list_id_idx ON cd_list_cd_mappings (cd_list_id);
