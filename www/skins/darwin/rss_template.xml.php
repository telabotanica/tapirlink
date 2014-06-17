<?php 
/**
 * $Id: rss_template.xml.php 352 2007-04-23 12:46:04Z rdg $
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

if ( ! isset( $_REQUEST['c'] ) )
{
   die('Parameter "c" (concept id) not specified');
}

$concept_id = $_REQUEST['c'];

header ( 'Content-type: text/xml' );

?><searchTemplate xmlns="http://rs.tdwg.org/tapir/1.0"
                xmlns:xs="http://www.w3.org/2001/XMLSchema"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://rs.tdwg.org/tapir/1.0
                                    http://rs.tdwg.org/tapir/1.0/tapir.xsd
                                    http://www.w3.org/2001/XMLSchema
                                    http://www.w3.org/2001/XMLSchema.xsd">
  <externalOutputModel location="http://rs.tdwg.org/tapir/cs/dwc/1.4/model/rss2.xml"/>
  <filter>
    <equals>
      <concept id="<?php print($concept_id); ?>"/>
      <parameter name="v"/>
    </equals>
  </filter>
  <orderBy>
    <concept id="http://rs.tdwg.org/dwc/dwcore/DateLastModified" descend="true"/>
  </orderBy>
 </searchTemplate>
