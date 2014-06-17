
      <!-- ============ begin RESOURCE FORM ============= -->

      <!------------------ begin TOP MENU ------------------>
      <span class="section"><?php print($resource->GetCode()); ?></span>
      <br/>
      <br/>
      <table align="center" width="90%" cellspacing="1" cellpadding="1" bgcolor="#999999">
        <tr>
          <?php $config_manager = new TpConfigManager(); ?><?php $num_steps = $config_manager->GetNumSteps(); ?><?php for ($i = 1; $i <= $num_steps; ++$i): ?><?php $wiz = $config_manager->GetWizardPage($i); ?> 
          <td align="center" valign="middle" width="<?php print(100/$num_steps); ?>%" bgcolor="#f5f5ff"><a href="<?php print($_SERVER['PHP_SELF']); ?>?form=<?php print($i); ?>&resource=<?php print($resource->GetCode()); ?>" class="text"><?php print($wiz->GetLabel()); ?></a></td><?php endfor; ?>
        </tr>
      </table>
      <!------------------- end TOP MENU ------------------->

      <?php if (count($errors)) printf("\n<br/><span class=\"error\">%s</span>", nl2br(implode('<br/>', $errors))); ?><?php if ($this->mMessage) printf("\n<br/><span class=\"msg\">%s</span>", nl2br($this->mMessage)); ?>

      <br/>
      <br/>
      <table width="60%" border="0">
       <tr>
        <td class="label" width="20%" valign="top" align="left">Status:</td>
        <td width="80%" valign="top" align="left">
          <?php print($resource->GetStatus()); ?>
          <pre>
<span class="tip">metadata............<?php if ($resource->ConfiguredMetadata()): ?>OK (complete and valid)<?php else: ?>incomplete or invalid<?php endif; ?></span>
<span class="tip">data source.........<?php if ($resource->ConfiguredDatasource()): ?>OK (just checked completeness)<?php else: ?>incomplete<?php endif; ?></span>
<span class="tip">tables..............<?php if ($resource->ConfiguredTables()): ?>OK (just checked completeness)<?php else: ?>incomplete<?php endif; ?></span>
<span class="tip">local filter........<?php if ($resource->ConfiguredLocalFilter()): ?>OK (just checked completeness)<?php else: ?>incomplete<?php endif; ?></span>
<span class="tip">mapping.............<?php if ($resource->ConfiguredMapping()): ?>OK (just checked completeness)<?php else: ?>incomplete<?php endif; ?></span>
<span class="tip">settings............<?php if ($resource->ConfiguredSettings()): ?>OK (just checked completeness)<?php else: ?>incomplete<?php endif; ?></span>
          </pre>
          <?php if (TpDiagnostics::Count()): ?><span class="tip"><?php print(nl2br(TpDiagnostics::Dump())); ?></span><br/><br/><?php endif; ?>
        </td>
       </tr>
<?php if ($resource->ConfiguredDatasource() and $resource->ConfiguredMapping()): ?>
       <tr>
        <td class="label" valign="top" align="left">Encoding:</td>
        <td valign="top" align="left">
          <pre>
<span class="tip">Click <a href="check_encoding.php?res=<?php print($resource->GetCode()); ?>">here</a> to run a test</span>
          </pre>
        </td>
       </tr>
<?php endif; ?>
       <tr>
        <?php $metadata_rev = $resource->GetMetadataTemplateRevision(); ?>
        <?php $capabilities_rev = $resource->GetCapabilitiesTemplateRevision(); ?>
        <td class="label" valign="top" align="left">Templates:</td>
        <td valign="top" align="left">
          <pre>
<span class="tip">metadata............<?php if ($metadata_rev): ?>revision <?php print($metadata_rev); ?> (<?php if ($metadata_rev < 472): ?>outdated template - click on metadata and save<?php else: ?>OK<?php endif; ?>)<?php else: ?>no revision<?php if ($resource->GetStatus() == 'active'): ?> (outdated template - click on metadata and save)<?php endif; ?><?php endif; ?></span>
<span class="tip">capabilities........<?php if ($capabilities_rev): ?>revision <?php print($capabilities_rev); ?> (<?php if ($capabilities_rev < 641): ?>outdated template - click on settings and save<?php else: ?>OK<?php endif; ?>)<?php else: ?>no revision<?php if ($resource->GetStatus() == 'active'): ?> (outdated template - click on settings and save)<?php endif; ?><?php endif; ?></span>
          </pre>
        </td>
       </tr>
       <tr>
        <?php $accesspoint = $resource->GetAccesspoint(); ?>
        <td class="label" valign="top" align="left">Accesspoint:</td>
        <td valign="top" align="left"><?php if (strlen($accesspoint)): ?><a href="<?php print($accesspoint); ?>"><?php print($accesspoint); ?></a><span class="tip"><?php print($access_msg); ?></span><?php else: ?>?<?php endif; ?></td>
       </tr>
      </table>
      <br/>
      <br/>
      <br/>
      <form name="resource" action="<?php print($_SERVER['PHP_SELF']); ?>" method="post">
      <input type="hidden" name="resource" value="<?php print(TpUtils::GetVar('resource')); ?>"/>
      <input type="hidden" name="refresh" value=""/>
      <input type="hidden" name="scroll"/>
      <input type="submit" name="remove" value="remove this resource" onClick="return confirmRemoval()"/>
      </form>
      <br/>
      <?php if ($access_ok): ?><form name="test" action="client_wrapper.php" method="get"><input type="hidden" name="local_accesspoint" value="<?php print($accesspoint); ?>"/><input type="submit" name="test" value="test with client form"/></form><?php endif; ?>
      <br/>
      

      <!-- ============= end RESOURCE FORM ============== -->
