
      <!-- ============ begin import FORM ============= -->
      <span class="section">Import DiGIR configuration</span>
      <br/>
      <?php if (count($errors)) printf("\n<br/><span class=\"error\">%s</span>", nl2br(implode('<br/>', $errors))); ?><?php if ($this->mMessage) printf("\n<br/><span class=\"msg\">%s</span>", nl2br($this->mMessage)); ?>
      <br/>
      <br/>
      <br/>
      <form name="import" action="<?php print($_SERVER['PHP_SELF']); ?>" method="post">
      <input type="hidden" name="import" value="1"/>
      <table border="0" width="90%">
       <tr>
        <td width="30%" class="label" align="left" nowrap="nowrap">DiGIR config directory: </td>
        <td width="70%" align="left"><input type="text" name="config_dir" value="<?php print( TpUtils::GetVar('config_dir', '') ); ?>" size="50"/></td>
       </tr>
       <tr>
        <td class="label" align="left" nowrap="nowrap">Resources file: </td>
        <td align="left"><input type="text" name="resource_file" value="<?php print( TpUtils::GetVar('resource_file', 'resources.xml') ); ?>" size="50"/></td>
       </tr>
       <tr>
        <td class="label" align="left" nowrap="nowrap">Provider metadata file: </td>
        <td align="left"><input type="text" name="metadata_file" value="<?php print( TpUtils::GetVar('metadata_file', 'providerMeta.xml') ); ?>" size="50"/>&nbsp;<input type="submit" name="show_resources" value="show resources"/></td>
       </tr>
      </table>
      <br/>
      <?php if(count($available_resources)): ?>
      <span class="label">Available Resources</span>
      <br/><br/>
      <table width="60%" cellspacing="1" cellpadding="1" bgcolor="#FFFFFF">
       <tr>
        <td width="20%" align="center"><input type="checkbox" class="checkbox" name="checkall_all" value="check_all" onclick="javascript:InvertAll();"/></td>
        <td width="80%" align="left" class="tip">&nbsp;&nbsp;( invert selection )</td>
       </tr>
      </table>
      <table width="60%" cellspacing="1" cellpadding="1" bgcolor="#999999">
      <?php foreach ($available_resources as $name => $config_file): ?>
       <tr>
        <td width="20%" align="center" bgcolor="#f5f5ff"><input type="checkbox" class="checkbox" name="resources[]" value="<?php print($name); ?>"<?php if (in_array($name, TpUtils::GetVar('resources', array()))): ?> checked="1"<?php endif; ?>/></td>
        <td width="80%" align="left" bgcolor="#f5f5ff">&nbsp;&nbsp;<?php print($name); ?></td>
       </tr>
      <?php endforeach; ?>
      </table>
      <br/>
      <input type="submit" name="process" value="import selected resources"/>
      <?php endif; ?>

      </form>
      <!-- ============= end import FORM ============== -->
