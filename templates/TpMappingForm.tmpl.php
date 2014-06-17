
<!-- beginning of MappingForm -->
<br/>
<?php $unmapped_schemas = $r_local_mapping->GetUnmappedSchemas(); ?>
<table align="center" width="95%" cellspacing="1" cellpadding="1" bgcolor="#FFFFFF">
<?php if ( count($unmapped_schemas) > 0 ): ?>
  <tr bgcolor="#ffffff">
    <td width="30%" align="left" nowrap="nowrap"><span class="label">Available schemas to map: </span></td>
    <td width="70%" align="left" nowrap="nowrap">
      <?php foreach ( $unmapped_schemas as $namespace => $schema): ?>
      <input type="checkbox" class="checkbox" name="schema[]" value="<?php print($namespace); ?>"/>&nbsp;<span class="label"><?php print($schema->GetAlias()); ?></span>&nbsp;<span class="tip"><?php print('('.$namespace.')'); ?></span><br/>
      <?php endforeach; ?>
    </td>
  </tr>
<?php endif; ?>
  <tr bgcolor="#ffffff">
    <td width="30%" align="left" nowrap="nowrap"><span class="label">Location of additional schema: </span></td>
    <td width="70%" align="left" nowrap="nowrap"><input type="text" name="load_from_location" value="<?php TpUtils::GetVar('location'); ?>" size="45"/>&nbsp;<?php print(TpHtmlUtils::GetCombo('handler','0',$this->GetOptions('handler'))); ?>&nbsp;<a href="help_cs_drivers.php" class="label_required" onClick="javascript:window.open('help_cs_drivers.php','help','width=800,height=550,menubar=no,toolbar=no,scrollbars=yes,resizable=yes,personalbar=no,locationbar=no,statusbar=no').focus(); return false;" onMouseOver="javascript:window.status='more information about conceptual schema drivers'; return true;" onMouseOut="window.status=''; return true;">?</a></td>
  </tr>
</table>
<br/><input type="submit" name="load_schemas" value="load the specified schemas above"/><br/>
<?php $has_mandatory_concepts = false; ?>
<?php foreach ( $r_local_mapping->GetMappedSchemas() as $namespace => $schema): ?>
  <br/>
  <span class="section"><?php print($schema->GetAlias()); ?></span><br/>
  <span class="tip"><?php print('('.$namespace.')'); ?>
  <br/>
  <br/>
  <input type="submit" name="unmap" value="unmap" onclick="document.wizard.refresh.value='<?php print($namespace.'^unmap'); ?>';window.saveScroll();document.wizard.submit();"/>
  &nbsp;&nbsp;
  <input type="submit" name="automap" value="automap" onclick="document.wizard.refresh.value='<?php print($namespace.'^automap'); ?>';window.saveScroll();document.wizard.submit();"/>
  &nbsp;&nbsp;
  <input type="submit" name="fill" value="fill unmapped" onclick="document.wizard.refresh.value='<?php print($namespace.'^fill'); ?>';window.saveScroll();document.wizard.submit();"/>
  <br/>
  <br/>
  <table align="center" width="95%" cellspacing="1" cellpadding="1" bgcolor="#999999">
    <tr bgcolor="#ffffee">
      <td class="label" width="25%">concept</td>
      <td class="label" width="5%" align="center">searchable</td>
      <td class="label" width="70%" align="center">mapping</td>
    </tr>
    <?php foreach ($schema->GetConcepts() as $concept_id => $concept): ?>
    <?php $mapping_input_name = $this->GetInputName($concept, 'mapping'); ?>
    <tr bgcolor="#ffffee">
      <td align="left" class="<?php if ($concept->IsRequired()): ?>label_required<?php $has_mandatory_concepts = true; ?><?php else: ?>label<?php endif; ?>" nowrap="nowrap">&nbsp;<?php if ($concept->IsRequired()): ?><?php print(TP_MANDATORY_FIELD_FLAG); ?><?php endif; ?><?php if (TpUtils::IsUrl($concept->GetDocumentation())): ?><a href="<?php print($concept->GetDocumentation()); ?>" target="_new"><?php endif; ?><?php print($concept->GetName()); ?><?php if (TpUtils::IsUrl($concept->GetDocumentation())): ?></a><?php endif; ?></td>
      <td align="center"><input type="checkbox" class="checkbox" name="<?php print($this->GetInputName($concept, 'searchable')); ?>" value="1"<?php if ($concept->IsSearchable()): ?> checked="1"<?php endif; ?>\></td>
      <td align="left" class="text" nowrap="nowrap"><?php print(TpHtmlUtils::GetCombo($mapping_input_name, $concept->GetMappingType(), TpConceptMappingFactory::GetOptions('mapping_types'), false, false, "document.wizard.refresh.value='$mapping_input_name';window.saveScroll();document.wizard.submit();")); ?>&nbsp;<?php print($concept->GetMappingHtml()); ?>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if ($has_mandatory_concepts): ?>
  <p class="tip"><?php print(TP_MANDATORY_FIELD_FLAG); ?>Indicates mandatory concepts</p>
  <?php endif; ?>
<?php endforeach; ?>

<br/>
<!-- end of MappingForm -->
