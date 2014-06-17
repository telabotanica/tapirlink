<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.0 //EN">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <title>TapirLink Configurator</title>

  <link rel="StyleSheet" href="layout.css" type="text/css">

  <!-- on the fly javascript -->
  <script language="JavaScript" type="text/javascript">
  <!-- 

  function getScroll()
  {
    return "<?php print($page->GetScroll()); ?>";
  }

  <?php print($page->GetJavascript()); ?>

  //-->
  </script>

  <!-- common functions -->
  <script language="JavaScript" src="utils.js" type="text/javascript"></script>

</head>

<body bgcolor="#FFFFFF" onLoad="javascript:loadScroll();">

<table width="100%" border="0" align="center" cellspacing="2" cellpadding="4">
  <tr>
    <td width="15%" align="left" valign="top">

      <!-- =============== begin SIDE MENU ============== -->

      <a href="<?php print($_SERVER['PHP_SELF']); ?>?force_reload=1" class="title">TapirLink<br/>configurator</a><br/><br/>
      <span class="tip">Resources</span><br/><br/>
      <?php if (count($resources_list) > 0): ?>
      <table border="0" width="90%" cellspacing="0" cellpadding="2">
        <?php foreach ($resources_list as $res): ?>
          <tr><td class="menu"><a href="<?php print($_SERVER['PHP_SELF']); ?>?resource=<?php print($res->GetCode()); ?>" class="menu"><?php print($res->GetCode()); ?></a></td></tr>
        <?php endforeach; ?>
      </table>
      <?php else: ?>
        <span class="menu">none</span><br/>
      <?php endif; ?>
      <br/>
      <form name="resources" action="<?php print($_SERVER['PHP_SELF']); ?>" method="GET">
        <input type="submit" name="add_resource" value="add"/><br/><br/>
        <input type="submit" name="import" value="import"/>
        <input type="hidden" name="first" value="1"/>
      </form>
      <?php if (count($resources_list) > 0): ?>
      <br/>
      <span class="tip">Tools</span><br/>
      <br/>
      <a href="<?php print($_SERVER['PHP_SELF']); ?>?uddi=1&amp;first=1" class="menu">UDDI</a>
      <?php endif; ?>

      <!-- ================ end SIDE MENU ================ -->

    </td>
    <td width="85%" align="center" valign="top">

      <!-- =============== begin MAIN FRAME ============== -->

<?php $page->displayHtml(); ?>

      <!-- ================ end MAIN FRAME =============== -->

    </td>
  </tr>
</table>

<?php 
if (_DEBUG) {
echo '<br/>';
echo '<br/>';
echo '<span class="tip">';
print('SERVER_NAME='.$_SERVER['SERVER_NAME'].'<br/>');
print('SERVER_PORT='.$_SERVER['SERVER_PORT'].'<br/>');
print('SCRIPT_NAME='.$_SERVER['SCRIPT_NAME'].'<br/>');
print('QUERY_STRING='.$_SERVER['QUERY_STRING'].'<br/>');
print('DOCUMENT_ROOT='.$_SERVER['DOCUMENT_ROOT'].'<br/>');
print('REQUEST_URI='.$_SERVER['REQUEST_URI'].'<br/>');
print('REQUEST_METHOD='.$_SERVER['REQUEST_METHOD'].'<br/>');
if ( isset( $_SERVER['PATH_TRANSLATED'] ) )
{
    print('PATH_TRANSLATED='.$_SERVER['PATH_TRANSLATED'].'<br/>');
}
if ( isset( $_SERVER['REMOTE_HOST'] ) )
{
    print('REMOTE_HOST='.$_SERVER['REMOTE_HOST'].'<br/>');
}

foreach ( $_REQUEST as $key => $val ) {

    print("$key => $val <br/>");
}
echo '</span>';
}
?>
</body>
</html>
