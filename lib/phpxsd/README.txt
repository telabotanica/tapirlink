ABOUT THE SOFTWARE
==================

phpxsd is a PHP library to parse XML Schemas and create an object 
representation that can be used to navigate the whole schema structure 
programatically. This library has been originally developed as part of 
TapirLink for dynamic generation of XML based on XML Schema, and it was 
later separated from it.


ACKNOWLEDGEMENTS
================ 

The design of most XML Schema classes was largely based on the API 
documentation of the xsom Java package (https://xsom.dev.java.net/) 
written by Kohsuke Kawaguchi.


SUPPORTED XML SCHEMA FEATURES
=============================

schema
import
include
element
attribute (declaration and use)
simple type
complex type
extensions (simple or complex content)
model group (sequence, all, choice)
references


HOW TO INSTALL
==============

Requirements: PHP >= 4.2.3.

Just extract the files into a directory accessible to your scripts.


HOW TO USE
==========

<?php

require_once('some_dir/phpxsd/XsSchemaParser.php');

$xsd = new XsSchemaParser();

$xsd->Parse( 'some_xml_schema' );

$my_schema_browser = new MyClassImplementingXsSchemaVisitor();

// note: see TpXmlGenerator.php in the TapirLink software for an example:
// http://digir.svn.sourceforge.net/viewvc/digir/tapirlink/trunk/classes/TpXmlGenerator.php?view=markup

$my_schema_browser->DoSomething();

?>


HOW TO COLLABORATE
==================

Anyone willing to collaborate is more than welcome. 
Please contact the author about your plans and for SVN access: 
renato at cria dot org dot br

Please note that you should follow this coding standard:

http://www.dagbladet.no/development/phpcodingstandard/

These additional guidelines are also adopted:

* Each class has its own file with the same name.
* Class properties are always manipulated through accessors and mutators.
* Custom library functions that are widely used should also be defined inside
  classes and accessed through the :: operator.
* Private and protected methods have an underscore prepended in their name.
* Please give preference to longer and clearer variable, function and class 
  names.
* Do not use TABs for indentation. Use 4 spaces.
* The library should be compatible with PHP 4.2.3 and later versions.

It is recommended to subscribe to the following mailing list to get automatic 
notifications about any changes in code:

http://lists.sourceforge.net/mailman/listinfo/digir-cvs


TODO LIST
=========

Parse and represent all missing XML Schema constructs.


BUG REPORTS & SUGGESTIONS
=========================

Please contact the author.


