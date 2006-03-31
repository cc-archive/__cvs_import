
--
-- Creative Commons
-- RDF Search
-- written by Ben Adida (ben@mit.edu)
--
-- 10/24/2003
--
-- this is for storing the licenses
--
-- GNU GPL v2
--

create sequence rdfs_license_id_seq;

-- licenses
create table rdfs_licenses (
    license_id          integer not null
                        constraint rdfs_license_pk primary key,
    license_url         varchar(250) not null
                        constraint rdfs_license_url_un unique,
    properties          varchar(50)
);

create or replace function rdfs_get_license_id(varchar)
returns integer as '
DECLARE
    p_license_url           alias for $1;
BEGIN
    insert into rdfs_licenses
    (license_id, license_url)
    select nextval(''rdfs_license_id_seq''), p_license_url
    where not exists (select 1 from rdfs_licenses where license_url = p_license_url);

    return license_id from rdfs_licenses where license_url= p_license_url;
END;
' language 'plpgsql';