<?php 
/**
 * $Id: model.xml.php 368 2007-05-22 23:39:23Z rdg $
 * 
 * LICENSE INFORMATION
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * 
 * http://www.gnu.org/copyleft/gpl.html
 * 
 * 
 * @author Renato De Giovanni <renato [at] cria . org . br>
 */

require_once('../../tapir_globals.php');
require_once('TpRequest.php');
require_once('TpResources.php');
require_once('TpResource.php');
require_once('TpLocalMapping.php');

if ( ! isset( $_REQUEST['a'] ) )
{
   die('Parameter "a" (accesspoint) not specified');
}
if ( ! isset( $_REQUEST['c'] ) )
{
   die('Parameter "c" (concept id) not specified');
}
if ( ! isset( $_REQUEST['v'] ) )
{
   die('Parameter "v" (filter value) not specified');
}

// Instantiate request object to get resource code
$request = new TpRequest();

// If no resource was specified in the request URI, display error
if ( ! $request->ExtractResourceCode( $_REQUEST['a'] ) )
{
   die('Could not determine resource');
}

// Get resource code and check if it's valid
$resource_code = $request->GetResourceCode();

$r_resources =& TpResources::GetInstance();

$raise_errors = false;
$r_resource =& $r_resources->GetResource( $resource_code, $raise_errors );

if ( $r_resource == null )
{
    die('Could not find resource "'.$resource_code.'"');
}

// Get all mapped concepts
$r_local_mapping =& $r_resource->GetLocalMapping();

$r_local_mapping->LoadFromXml( $r_resource->GetConfigFile() );

$r_mapped_schemas =& $r_local_mapping->GetMappedSchemas();

$concepts = array();

foreach ( $r_mapped_schemas as $ns => $conceptual_schema )
{
    $r_concepts =& $r_mapped_schemas[$ns]->GetConcepts();

    foreach ( $r_concepts as $concept_id => $concept )
    {
        if ( $r_concepts[$concept_id]->IsMapped() )
        {
            $concepts[$concept_id] = $r_concepts[$concept_id]->GetName();
        }
    }
}

header ( 'Content-type: text/xml' );

?><outputModel xmlns="http://rs.tdwg.org/tapir/1.0"
             xmlns:xs="http://www.w3.org/2001/XMLSchema"
             xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:schemaLocation="http://rs.tdwg.org/tapir/1.0
                                 http://rs.tdwg.org/tapir/1.0/tapir.xsd
                                 http://www.w3.org/2001/XMLSchema
                                 http://www.w3.org/2001/XMLSchema.xsd">
  <structure>
    <xs:schema targetNamespace="http://tapirlink/model/search/default">
      <xs:element name="records">
        <xs:complexType>
          <xs:sequence>
            <xs:element name="record" minOccurs="0" maxOccurs="unbounded">
              <xs:complexType>
                <xs:sequence>
                  <?php $i = 0; ?>
                  <?php foreach ( $concepts as $concept_id => $alias ): ?>
                  <?php ++$i; ?>
                  <xs:element name="field<?php print($i); ?>">
                    <xs:complexType>
                      <xs:sequence>
                        <xs:element name="name" type="xs:string" fixed="<?php print($alias); ?>"/>
                        <xs:element name="value" type="xs:string" nillable="true"/>
                      </xs:sequence>
                    </xs:complexType>
                  </xs:element>
                  <?php endforeach; ?>
                </xs:sequence>
              </xs:complexType>
            </xs:element>
          </xs:sequence>
          <xs:attribute name="concept" type="xs:string" use="required" fixed="<?php print($_REQUEST['c']); ?>"/>
          <xs:attribute name="value" type="xs:string" use="required" fixed="<?php print($_REQUEST['v']); ?>"/>
        </xs:complexType>
      </xs:element>
    </xs:schema>
  </structure>
  <indexingElement path="/records/record"/>
  <mapping>
    <?php $i = 0; ?>
    <?php foreach ( $concepts as $concept_id => $alias ): ?>
    <?php ++$i; ?>
    <node path="/records/record/field<?php print($i); ?>/value">
      <concept id="<?php print($concept_id); ?>"/>
    </node>
    <?php endforeach; ?>
  </mapping>
 </outputModel>
