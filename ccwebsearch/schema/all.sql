
--
-- Data Model loading for CC RDF Search
--
-- ben@mit.edu
--

-- run on command line
-- createlang plpgsql cc

-- load up the actual data model
\i license-data-model.sql
\i url-data-model.sql
\i url-gathering-data-model.sql
\i document-data-model.sql

-- load the licenses
\i licenses.sql
