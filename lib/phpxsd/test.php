<?php

require_once('XsSchemaParser.php');
require_once('XsSchemaNavigator.php');

$schema = new XsSchemaParser();

$schema->Parse( 'http://rs.tdwg.org/tapir/rs/dw_v14_rdf_record.xsd' );

$navigator = new XsSchemaNavigator( $schema );

$navigator->navigate();

?>