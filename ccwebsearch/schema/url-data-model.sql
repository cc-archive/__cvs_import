
--
-- Creative Commons
-- RDF Search
-- written by Ben Adida (ben@mit.edu)
--
-- 10/24/2003
--
-- This implements the data model for storing URLs that have been gathered and whose
-- content needs to be fetched.
--
-- GNU GPL v2
--

-- sequences
create sequence rdfs_url_method_id_seq;

-- the various methods for gathering URLs
-- each one needs to be registered so we can keep
-- track of where we got the URL from
create table rdfs_url_methods (
        method_id           integer not null
                            constraint rdfs_url_meth_pk primary key,
        method              varchar(100) not null
                            constraint rdfs_url_meth_un unique
);

-- the gathered URLs
-- including information on the last time they were checked
create table rdfs_urls (
        url                     varchar(250) not null
                                constraint rdfs_url_url_pk primary key,
        -- the URL gathering method by which this URL was found
        method_id               integer not null
                                constraint rfds_url_meth_id_fk
                                references rdfs_url_methods(method_id),
        -- when the URL was first entered
        creation_date           timestamp not null,
        -- the last time this URL content was downloaded
        last_download           timestamp,
        -- We keep track of whether a URL is being fetched by some thread
        -- and what that thread is. This allows parallelizing the content fetching
        current_download_begin  timestamp,
        current_download_pid    varchar(30),
        -- state information (we keep track of pages that might go dead)
        state               varchar(30) default 'alive' not null
                            constraint rdfs_urls_state_ch
                            check (state in ('alive', 'comatose', 'dead'))
);

--
-- A function to create a new method for gathering URLs
--
create or replace function rdfs_get_method_id(varchar)
returns integer as '
DECLARE
    method_name                 alias for $1;
    v_method_id                 integer;
BEGIN
    insert into rdfs_url_methods
    (method_id, method)
    select nextval(''rdfs_url_method_id_seq''), method_name
    where not exists (select 1 from rdfs_url_methods where method = method_name);

    select method_id into v_method_id
    from rdfs_url_methods where method = method_name;

    return v_method_id;
END;
' language 'plpgsql';


create or replace function rdfs_url_new(varchar, integer)
returns integer as '
DECLARE
    v_url                       alias for $1;
    v_method_id                 alias for $2;
    v_url_id                    integer;
BEGIN
    insert into rdfs_urls
    (url, method_id, creation_date)
    select
    v_url, v_method_id, now()
    where not exists (select 1 from rdfs_urls where url = v_url);

    return 0;
END;
' language 'plpgsql';

create or replace function rdfs_url_touch(varchar)
returns integer as '
DECLARE
    p_url           alias for $1;
BEGIN
    update rdfs_urls set last_download=now()
    where url = p_url;

    return 0;
END;
' language 'plpgsql';