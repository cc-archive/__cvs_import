@echo off
d:
cd \cchost\cctools
if exist \cchost\cctools\apidocsym\*.sym del \cchost\cctools\apidocsym\*.sym
if exist \cchost\cctools\apidoc\*.html   del \cchost\cctools\apidoc\*.html

rem THE REAL THING:
for %%I in (..\cclib\cc*.php) do php -q -f gen_doc.php /c cclib\%%~nxI /o \cchost\cctools\apidocsym

rem FOR TESTING
rem php -q -f gen_doc.php /c ../cclib/cc-contest.php /o \cchost\cctools\apidocsym

php -q -f gen_doc.php /x                         /o \cchost\cctools\apidocsym
php -q -f gen_doc.php /g \cchost\cctools\apidocsym /o \cchost\cctools\apidoc
php -q -f gen_doc.php /i \cchost\cctools\apidocsym /o \cchost\cctools\apidoc\index.html
