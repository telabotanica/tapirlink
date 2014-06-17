<html>
<head>
	<title><?=$ClassName?> Documentation.</title>
	<link href="<?=$ClassName?>.css.php" rel="STYLESHEET" type="text/css">
</head>
<body>

<!-- ################################################################## -->

<small>
	This page contains automatically generated documentation.
	Last updated: 
	<?php
	$aAttrib = stat($xmlFile);
	if ($aAttrib) {
		 echo date ("d F Y H:i:s.", $aAttrib[9]);
	}
	?>
	<a href="#UpdateInstructions">Updating instructions</a>
</small>

<!-- ################################################################## -->

<hr>

<?php

// Get the contents of the files, using fopen($fileName, "r");
// Both calls work ok.
$xslData = implode('',file($xslFile));
$xmlData = implode('',file($xmlFile));

$hXslt = xslt_create();

$arguments = array(
     '/_xml' => $xmlData,
     '/_xsl' => $xslData
);
$result = xslt_process($hXslt, 'arg:/_xml', 'arg:/_xsl', NULL, $arguments);

if ($result) {
    echo $result;
} else {
    echo "There was an error that occurred in the XSL transformation...\n";
    echo "\tError number: " . xslt_errno() . "\n";
    echo "\tError string: " . xslt_error() . "\n";
    exit;
}

xslt_free($hXslt);

?>

<!-- ################################################################## -->

<hr id="UpdateInstructions">

<p>To update the documentation, run the GeneratePhpDocumentation.pl script on your 
copy of <?=$FileName?> and pipe the output to <?=$xmlFile?>.  Reloading this page 
will then show uptodate documentation for your version of <?=$ClassName?>.</p>
      
</body>
</html>