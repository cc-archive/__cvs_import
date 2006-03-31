
--
-- Creative Commons
-- RDF Search
-- written by Ben Adida (ben@mit.edu)
--
-- 11/21/2003
--
-- This implements the data model for keeping track of progress made
-- by each method in collecting URLs. We do this because URL gathering has become a bit difficult
--
--
-- GNU GPL v2
--

create sequence rdfs_gather_log_id_seq;

--
-- a table that logs the gathering work done so far
--
create table rdfs_gather_log (
    log_id                  integer not null
                            constraint rdfs_gather_log_id_pk primary key,
    method_id               integer not null
                            constraint rdfs_gather_method_id_fk references rdfs_url_methods(method_id),
    -- gather_category could be a single license
    gather_category         varchar(250) not null,
    -- start is a #, like 100, that says that we searched URL starting with 101
    start                   integer not null,
    -- range is the # of URLs
    range                   integer not null,
    gather_date             timestamp default now() not null
);


-- a function to make logging easier
create or replace function rdfs_gather_log(integer, varchar, integer, integer)
returns integer as '
DECLARE
    p_method_id         alias for $1;
    p_category          alias for $2;
    p_start             alias for $3;
    p_range             alias for $4;
    v_log_id            integer;
BEGIN
    insert into rdfs_gather_log
    (log_id, method_id, gather_category, start, range, gather_date) values
    (nextval(''rdfs_gather_log_id_seq''), p_method_id, p_category, p_start, p_range, now());

    return currval(''rdfs_gather_log_id_seq'');
END;
' language 'plpgsql';