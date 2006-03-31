
--
-- Creative Commons
-- RDF Search
-- written by Ben Adida (ben@mit.edu)
--
-- 10/24/2003
--
-- CHANGELOG
--
-- 11/21/2003: made rdf fields nullable, because lots of docs won't have them.
--
-- This implements the data model for storing documents that have been fetched
--
-- GNU GPL v2
--

--
-- The documents that are stored
--
create table rdfs_documents (
    url                     varchar(250) not null
                            constraint rdfs_doc_url_pk primary key
                            constraint rdfs_doc_url_fk references rdfs_urls(url),
    download_date           timestamp not null,
    -- RDF information
    license_id              integer
                            constraint rdfs_doc_license_fk references rdfs_licenses,
    title                   varchar(250),
    copyright_date          varchar(50),
    description             text,
    creator                 varchar(200),
    doc_type                varchar(100),
    -- non RDF
    -- we take out the HTML
    stripped_content        text,
    -- full text indexing
    fti_index               tsvector
);



--
-- some functions
--

create or replace function rdfs_grab_urls(integer, varchar, reltime)
returns setof rdfs_urls as '
DECLARE
    p_num               alias for $1;
    p_pid               alias for $2;
    p_since             alias for $3;
    v_record            RECORD;
BEGIN
    -- clear things from a past run
    update rdfs_urls set current_download_begin=NULL, current_download_pid=NULL
    where current_download_pid= p_pid;

    -- return the URLs (and prepare them)
    for v_record in select * from rdfs_urls where current_download_pid is NULL and (last_download + p_since < now() or last_download is NULL) limit p_num for update
    LOOP
        update rdfs_urls set current_download_pid= p_pid, current_download_begin=now()
        where url = v_record.url;
        return next v_record;
    END LOOP;

    return;
END;
' language 'plpgsql';

--
-- a function to store a single document
--
create or replace function rdfs_store_document(varchar,integer,varchar,varchar,text,varchar,varchar,text)
returns varchar as '
DECLARE
    p_url                   alias for $1;
    p_license_id            alias for $2;
    p_title                 alias for $3;
    p_copyright_date        alias for $4;
    p_description           alias for $5;
    p_creator               alias for $6;
    p_doc_type              alias for $7;
    p_stripped_content      alias for $8;
BEGIN
    -- insert a blank row for now
    insert into rdfs_documents (url, download_date) select p_url, now()
    where not exists (select 1 from rdfs_documents where url = p_url);

    -- update it
    update rdfs_documents set
    license_id = p_license_id,
    title = p_title,
    copyright_date = p_copyright_date,
    description = p_description,
    creator = p_creator,
    doc_type = p_doc_type,
    stripped_content = p_stripped_content
    where
    url = p_url;

    -- mark it done downloading
    update rdfs_urls set current_download_pid= NULL, current_download_begin= NULL
    where url = p_url;

    PERFORM rdfs_url_touch(p_url);

    return p_url;
END;
' language 'plpgsql';


-- the index
create index rdfs_fti_idx on rdfs_documents using gist(fti_index);

-- the trigger
create trigger fti_update
before update or insert on rdfs_documents
for each row execute procedure tsearch2(fti_index, creator, title, stripped_content);