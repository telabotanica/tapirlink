<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?php require_once('tapir_globals.php'); ?>
<head>
<title>TapirLink data provider</title>
<style type="text/css">
body {background-color: #ffffff; color: #000000;}
body, td, th, h1, h2 {font-family: sans-serif;}
pre {margin: 0px; font-family: monospace;}
a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
a:hover {text-decoration: underline;}
table {border-collapse: collapse;}
.center {text-align: center;}
.center table { margin-left: auto; margin-right: auto; text-align: left;}
.center th { text-align: center !important; }
td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccccff; font-weight: bold; color: #000000;}
.h {background-color: #9999cc; font-weight: bold; color: #000000;}
.v {background-color: #cccccc; color: #000000;}
.vr {background-color: #cccccc; text-align: right; color: #000000;}
img {float: right; border: 0px;}
hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
</style>
</head>
<body>
<h3><i>TapirLink data provider</i></h3>
<p>TapirLink provides accesspoints that conform to the <a href="http://rs.tdwg.org/tapir/">TAPIR</a> protocol. Each accesspoint is a separate service that can be used to programatically query one of the underlying resources (a local data source exposed to a federated network).</p>

<h3>Resources</h3>
<p>Interaction with this service can only be done through one of the active resources:</p>
<?php require_once('TpResources.php'); ?>
<?php $resources =& TpResources::GetInstance(); ?>
<?php if ( count($resources->GetActiveResources() ) ): ?>
<?php foreach($resources->GetActiveResources() as $resource): ?>
<a href="<?php print($resource->GetAccesspoint()); ?>"><?php print($resource->GetCode()); ?></a><br />
<?php endforeach; ?>
<?php else: ?>
<p>No active resources available.</p>
<?php endif; ?>

<h3>TAPIR parameters</h3>
<p>The following KVP (key-value pair) parameters can be used to interact with a TAPIR accesspoint using HTTP POST or GET. You can also try the built-in <a href="tapir_client.php">client interface</a> for full XML interaction. For more information about the parameters you can also check the <a href="http://www.tdwg.org/dav/subgroups/tapir/1.0/docs/">TAPIR specification</a>.</p>
<table>
<tr><td colspan="2">Global parameters:</td></tr>
<tr><td class="e" width="15%">op</td>
<td class="v" width="85%">[m | metadata] = Default operation to retrieve basic information about the service.<br />
[c | capabilities] = Used to retrieve the essential settings to properly interact with the service.<br />
[i | inventory] = Used to retrieve distinct values of one or more concepts.<br />
[s | search] = Main operation to search and retrieve data.<br />
[p | ping] = Used for monitoring purposes to check service availability.<br />
(default = metadata; cardinality = 1..1)</td></tr>
<tr><td class="e">xslt</td><td class="v">[ URI ] Gives the address of an XML style sheet to be included after the XML header or applied to the returned data.<br />(default = null; cardinality = 0..1)</td></tr>
<tr><td class="e">log-only</td><td class="v">[ true | false | 1 | 0 ] Used to indicate if the request should only be logged, not processed. Returns a log message instead of data.<br />(default = false; cardinality = 0..1)</td></tr>
<tr><td colspan="2">Query parameters (for inventory and search operations):</td></tr>
<tr><td class="e">cnt | count</td><td class="v">[ true | false | 1 | 0 ] Indicates if the total number of records must be returned.<br />(default = false; cardinality = 0..1)</td></tr>
<tr><td class="e">s | start</td><td class="v">[ positive integer >= 1 ] Index of the first record to be returned.<br />(default = 1; cardinality = 0..1)</td></tr>
<tr><td class="e">l | limit</td><td class="v">[ integer >= 0 ] The number of records to be returned.<br />(default = null; cardinality = 0..1)</td></tr>
<tr><td class="e">t | template</td><td class="v">The URL of a query template document associated with the operation.<br />(default = null; cardinality = 0..1)</td></tr>
<tr><td class="e">f | filter</td><td class="v">A <a href="http://www.tdwg.org/dav/subgroups/tapir/1.0/docs/">KVP filter expression</a>. This parameter is ignored when a template is specified.<br />(default = null; cardinality = 0..1)</td></tr>
<tr><td colspan="2">Inventory parameters:</td></tr>
<tr><td class="e">c | concept</td><td class="v">Comma-separated list of fully qualified concept identifiers or aliases. This parameter is ignored when a template is specified.<br />(default = null; cardinality = 0..n)</td></tr>
<tr><td colspan="2">Search parameters:</td></tr>
<tr><td class="e">e | envelope</td><td class="v">[ true | false | 1 | 0 ] Indicates if the TAPIR envelope (response, header and diagnostics tags) should be supressed or not.<br />(default = false; cardinality = 0..1)</td></tr>
<tr><td class="e">m | model</td><td class="v">[ URI ] A pointer to an output model document. This parameter is ignored when a template is specified.<br />(default = null; cardinality = 0..1)</td></tr>
<tr><td class="e">p | partial</td><td class="v">Comma-separated list of xpaths of nodes from the output model schema that should be returned. This parameter is ignored when a template is specified.<br />(default = null; cardinality = 0..n)</td></tr>
<tr><td class="e">o | orderby</td><td class="v">Comma-separated list of fully qualified concept identifiers or aliases to order results. This parameter is ignored when a template is specified.<br />(default = null; cardinality = 0..n)</td></tr>
</table>
</body>
</html>
