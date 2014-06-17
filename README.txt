ABOUT THIS SOFTWARE
===================

TapirLink is a data provider software compatible with the 
TAPIR protocol (http://www.tdwg.org/subgroups/tapir). 

"TAPIR specifies a standardised, stateless, HTTP transmittable, 
request and response protocol for accessing structured data that 
may be stored on any number of distributed databases of varied 
physical and logical structure."

Data provider software is one of the key components of TAPIR 
networks. All participants of a TAPIR network, ie. people or 
institutions that want to share data through TAPIR, need to 
install a data provider software. TapirLink allows data providers 
to map their local databases against one or more conceptual 
schemas (used as a data abstraction layer). It translates TAPIR 
search requests to the local query language and local database 
structure to return an XML response based on the output model 
requested.

ACKNOWLEDGEMENTS
================ 

TapirLink has been generously funded by the Biodiversity 
Information Standards, TDWG, with resources from the Gordon and 
Betty Moore Foundation. The Global Biodiversity Information 
Facility, GBIF, has also been a major supporter of the TAPIR 
initiative since the very beginning and also collaborated to 
test TapirLink.

This software was based on the DiGIR PHP provider, which was 
originally developed by Dave Vieglais from the Biodiversity 
Research Center, University of Kansas. Two other institutions 
participated in the DiGIR project: California Academy of Sciences 
and Museum of Vertebrate Zoology, Berkeley.

The KVP filter parsing functionality in TapirLink has been ported 
from PyWrapper code (http://www.pywrapper.org), whose main author 
is Markus Döring.

NOTES
=====

Although TapirLink is based on the DiGIR PHP provider, it is
NOT compatible with the DiGIR protocol. TapirLink is only 
compatible with the TAPIR protocol (version 1.0 of the 
protocol, as specified at: 
http://www.tdwg.org/activities/tapir/specification/

A single instance of TapirLink can provide access to multiple 
TAPIR resources, each one with its own address. After 
installing this software and adjusting your web server 
configuration, you may be tempted to guess that the access 
point of your service is something like:

http://example.net/tapirlink/tapir.php

However, this will only give you generic documentation about 
the service. Real interaction with the service can only happen 
through one of the end points. After configuring TapirLink, you 
will notice that each resource has a local id (or code). The 
local id must be appended to the previous URI to give you the 
corresponding address of the service, like: 

http://example.net/tapirlink/tapir.php/myres/

TapirLink can map conceptual schemas that either follow the 
DarwinCore patterns or the CNS configuration file patterns.

For specimen/observation data providers, TapirLink has been 
tested with:

* The original version of DarwinCore;
* The second generation of DarwinCore (with the curatorial and 
geospatial extensions);
* The official version of DarwinCore approved by TDWG
(DarwinCore terms);
* ABCD 2.06

For taxonomic data providers TapirLink has been tested with:

* TCS 1.01

TapirLink allows each resource to map one or more conceptual 
schemas, but it will only be able to serve instances of a single 
"class" or "entity". In other words, when mapping multiple 
conceptual schemas, each mapped concept will actually refer to an 
"attribute" or "property" of the same underlying class. In search 
responses, instances of that class will be bound to instances of 
the "indexingElement" defined in the output model.

This means that TapirLink has limited use with response structures 
that relate instances of different classes, for example multiple 
specimens, each one with multiple identifications. However, in 
these cases sometimes it is possible to use fixed value mappings,
especially when response structures include metadata elements 
(for example "collection code") enclosing all instances of a class.

FEATURES
========

* All TAPIR operations (metadata, capabilities, inventory, search
  and ping). 
* Request encoding can be KVP or XML.
* Inventories on any mapped concepts.
* Searches with any output models involving concepts from mapped 
  schemas.
* Response structures with basicSchemaLanguage.
* Several types of relational databases supported 
  (check http://phplens.com/adodb/supported.databases.html)
* Log only requests can be accepted.
* Complete filter parsing. "Equals" and "like" can be case sensitive 
  or not.
* Max element repetitions and max element levels settings.
* Multiple resources can be exposed from a single TapirLink instance.
* Each resource can map one or more conceptual schemas based on the 
  new DarwinCore pattern or the CNS configuration file format.
* User-friendly Web configuration interface including a UDDI 
  registration form and the possibility to import DiGIR configuration.
* A simple client for testing.
* An LSID authority resolver (see: www/lsid-authority/readme.html)

LIMITATIONS
===========

* Any XML Schema used as a response structure should not include or 
  import other schemas that redeclare the same prefix and associate
  it with a different namespace.

INSTALLATION
============

Please read the INSTALL.txt file for installation instructions.

TROUBLESHOOTING
===============

Try running in your browser the script admin/check.php to see if 
there are any problems with your installation.

If you are having problems with you PHP installation, run the 
scrip admin/info.php to check what are the extensions loaded, 
where is php.ini located, etc.

Activating error logging in your PHP installation is a good idea.
You just need to set the following options in you php.ini file:
log_errors = On
error_log = /some_directory/php.log

If you are getting unexpected blank pages in your browser when running 
scripts, try increasing the memory limit of your PHP instance. Look 
for the option "memory_limit" in your php.ini file. The default value 
in older PHP versions was 8M. Recent versions come with 128M. I suggest
at least 32M. TapirLink consumes more memory if you use it with big and 
complicated response structures or if you set the maximum number of 
element repetitions to a high value.

When you are having problems, it is also a good idea to activate 
debugging. To do this, copy www/localconfig_dist.php to 
www/localconfig.php and enable the line where _DEBUG is set to true. 
Remember to rollback this change when you are in a production environment.

Another PHP setting you might want to adjust is the socket timeout, 
especially if you are going to work with response structures that take 
a long time to load. Look for the "default_socket_timeout" option in the 
php.ini file.

Note: you need to restart your web server everytime you make changes
in php.ini!

If you need to submit any bug report, use the following service 
(category "TapirLink"):

https://sourceforge.net/tracker/?group_id=38190&atid=422311

Since TapirLink accesspoints incorporate the resource code as part of the
URI, some Web Servers may need special configuration to understand that
requests pointing to http://somehost/somepath/tapir.php/someresource
should actually run the tapir.php script. In Apache you can add a line 
like the following one to solve the problem:

AliasMatch (?i)^/somepath/tapir.php/(.*) C:/tapirlinkdir/www/tapir.php

In IIS you will need additional software to produce the same effects 
of rewrite rules. One option is ISAPI Rewrite Lite:

http://www.isapirewrite.com/ 

In this case, these are the steps:

- Install ISAPI Rewrite Lite
- Start IIS
- View Properties of the required Web Site in IIS
- On the ISAPI Filters tab, add a filter (with any suitable name) and
  browse to the installed ISAPI_Rewrite.dll.
- Modify the httpd.ini file in the Rewrite install directory to contain
  the required rewrite rule regular expression, which can be:

RewriteRule ^(.+\/)tapir.php\/([^\?\/]+)[\?\/]?(.*)
$1tapir.php?dsa=$2(?3&$3)

For more information about web server configuration in this case, try 
searching the web for "clean URLs". 

As a last resource, instead of using:

http://somehost/somepath/tapir.php/someresource

You can define your accesspoint as:

http://somehost/somepath/tapir.php?dsa=someresource

DEVELOPER GUIDELINES
====================

Anyone willing to collaborate is welcome. Please contact the author 
about your plans and for SVN access:
renato at cria dot org dot br

TapirLink follows this coding standard:

http://www.dagbladet.no/development/phpcodingstandard/

The following guidelines are also adopted:

* Each class has its own file with the same name.
* Class properties are always manipulated through accessors and mutators.
* Custom library functions that are widely used are also defined inside
  classes and accessed through the :: operator.
* Private and protected methods have an underscore prepended in their name.
* Please give preference to longer and clearer variable, function and class 
  names.
* Do not use TABs for indentation. Use 4 spaces.

It is recommended to subscribe to the following mailing list to get automatic 
notifications about any changes in code:

http://lists.sourceforge.net/mailman/listinfo/digir-cvs

Also remember that TapirLink code should remain compatible with both 
PHP4 and PHP5.
