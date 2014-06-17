<!doctype html public "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
<title>Help</title>
<link rel="StyleSheet" href="layout.css" type="text/css">
</head>
<body bgcolor="#FFFFFF">
<center>
<span class="section"><?php print( $name ); ?></span>
<br>
<div width="80%" class="box2">
<?php if ( isset( $a ) ): ?><p align="<?php print( $a ); ?>"><?php endif; ?>
<span class="msg"><?php print( nl2br( $doc ) ); ?></span>
<?php if ( isset( $a ) ): ?></p><?php endif; ?>
</div>
</center>
</body>
</html>