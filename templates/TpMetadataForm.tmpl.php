
<!-- beginning of MetadataForm -->
<div class="box1" align="left">
<?php $textarea_cols = 50; ?>

<!-- id -->
<?php print($this->GetHtmlLabel('id',true)); ?> 
<input type="text" name="id" value="<?php print($r_metadata->GetId()); ?>" size="30" onChange="javascript:changedLocalId();"/>
<br/>

<!-- type -->
<input type="hidden" name="type" value="<?php print(urlencode($r_metadata->GetType())); ?>"/>

<!-- created -->
<input type="hidden" name="created" value="<?php print(urlencode($r_metadata->GetCreated())); ?>"/>

<!-- accesspoint -->
<br/>
<?php print($this->GetHtmlLabel('accesspoint',true)); ?> 
<input type="text" name="accesspoint" value="<?php print($r_metadata->GetAccesspoint()); ?>" size="70"/>
<br/>

<!-- default language -->
<br/>
<?php print($this->GetHtmlLabel('default_language',false)); ?> 
<?php print(TpHtmlUtils::GetCombo('default_language',$r_metadata->GetDefaultLanguage(),$this->GetOptions('lang'))); ?>
<br/>

<!-- titles -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('title',true)); ?><br/>
<?php $cnt = 0; ?><?php $total = count($r_metadata->GetTitles()); ?>
<?php foreach ($r_metadata->GetTitles() as $title): ?><?php ++$cnt; ?>
<input type="text" name="title_<?php print($cnt); ?>" value="<?php print($title->GetValue()); ?>" size="65"/> 
<?php print(TpHtmlUtils::GetCombo('title_lang_'.$cnt,$title->GetLang(),$this->GetOptions('lang'))); ?> 
<?php if ($total > 1): ?><input type="submit" name="del_title_<?php print($cnt); ?>" value="remove" onClick="javascript:saveScroll();"/>
<?php endif; ?>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_title" value="add title" onClick="javascript:saveScroll();"/>
</div>

<!-- descriptions -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('description',true)); ?><br/>
<?php $cnt = 0; ?><?php $total = count($r_metadata->GetDescriptions()); ?>
<?php foreach ($r_metadata->GetDescriptions() as $description): ?><?php ++$cnt; ?>
<textarea name="description_<?php print($cnt); ?>" rows="5" cols="<?php print($textarea_cols); ?>" wrap="soft"><?php print($description->GetValue()); ?></textarea> 
<?php print(TpHtmlUtils::GetCombo('description_lang_'.$cnt,$description->GetLang(),$this->GetOptions('lang'))); ?> 
<?php if ($total > 1): ?><input type="submit" name="del_description_<?php print($cnt); ?>" value="remove" onClick="javascript:saveScroll();"/>
<?php endif; ?>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_description" value="add description" onClick="javascript:saveScroll();"/>
</div>

<!-- subjects -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('subjects',false)); ?><br/>
<?php $cnt = 0; ?>
<?php foreach ($r_metadata->GetSubjects() as $subjects): ?><?php ++$cnt; ?>
<input type="text" name="subjects_<?php print($cnt); ?>" value="<?php print($subjects->GetValue()); ?>" size="65"/> 
<?php print(TpHtmlUtils::GetCombo('subjects_lang_'.$cnt,$subjects->GetLang(),$this->GetOptions('lang'))); ?> 
<input type="submit" name="del_subjects_<?php print($cnt); ?>" value="remove" onClick="javascript:saveScroll();"/>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_subjects" value="add subjects" onClick="javascript:saveScroll();"/>
</div>

<!-- bibliographic citations -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('bibliographicCitation',false)); ?><br/>
<?php $cnt = 0; ?>
<?php foreach ($r_metadata->GetBibliographicCitations() as $bibliographicCitation): ?><?php ++$cnt; ?>
<textarea name="bibliographicCitation_<?php print($cnt); ?>" rows="5" cols="<?php print($textarea_cols); ?>" wrap="soft"><?php print($bibliographicCitation->GetValue()); ?></textarea> 
<?php print(TpHtmlUtils::GetCombo('bibliographicCitation_lang_'.$cnt,$bibliographicCitation->GetLang(),$this->GetOptions('lang'))); ?> 
<input type="submit" name="del_bibliographicCitation_<?php print($cnt); ?>" value="remove" onClick="javascript:saveScroll();"/>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_bibliographicCitation" value="add citation" onClick="javascript:saveScroll();"/>
</div>

<!-- rights -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('rights',false)); ?><br/>
<?php $cnt = 0; ?>
<?php foreach ($r_metadata->GetRights() as $rights): ?><?php ++$cnt; ?>
<textarea name="rights_<?php print($cnt); ?>" rows="5" cols="<?php print($textarea_cols); ?>" wrap="soft"><?php print($rights->GetValue()); ?></textarea> 
<?php print(TpHtmlUtils::GetCombo('rights_lang_'.$cnt,$rights->GetLang(),$this->GetOptions('lang'))); ?> 
<input type="submit" name="del_rights_<?php print($cnt); ?>" value="remove" onClick="javascript:saveScroll();"/>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_rights" value="add rights" onClick="javascript:saveScroll();"/>
</div>

<!-- underlying database (language and date last modified) -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('language',true)); ?> 
<?php print(TpHtmlUtils::GetCombo('language',$r_metadata->GetLanguage(),$this->GetOptions('content_lang'))); ?><br/>
<br/>
</div>

<!-- indexing preferences -->
<div class="box2" align="left" nowrap="nowrap">
<?php print($this->GetHtmlLabel('indexingPreferences',false)); ?><br/>
<br/>
<?php $indexingPreferences = $r_metadata->GetIndexingPreferences(); ?>
<?php print($this->GetHtmlLabel('startTime',false)); ?><?php print(TpHtmlUtils::GetCombo('hour',$indexingPreferences->GetHour(),$this->GetOptions('hour'))); ?><?php print(TpHtmlUtils::GetCombo('ampm',$indexingPreferences->GetAmPm(),$this->GetOptions('ampm'))); ?><?php print(TpHtmlUtils::GetCombo('timezone',$indexingPreferences->GetTimezone(),$this->GetOptions('timezone'))); ?>&nbsp;<?php print($this->GetHtmlLabel('maxDuration',false)); ?><?php print(TpHtmlUtils::GetCombo('maxDuration',$indexingPreferences->GetMaxDuration(),$this->GetOptions('maxDuration'))); ?>&nbsp;<?php print($this->GetHtmlLabel('frequency',false)); ?><?php print(TpHtmlUtils::GetCombo('frequency',$indexingPreferences->GetFrequency(),$this->GetOptions('frequency'))); ?><br/>
</div>

<!-- related entities -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('relatedEntities',true)); ?><br/>
<?php $cnt = 0; ?><?php $total = count($r_metadata->GetRelatedEntities()); ?>
<?php foreach ($r_metadata->GetRelatedEntities() as $related_entity): ?><?php ++$cnt; $entity = $related_entity->GetEntity(); $entity_prefix = "entity_$cnt"; ?>
<div class="box1" align="left">
<input type="hidden" name="<?php print($entity_prefix); ?>" value=""/>
<!-- entity identifier -->
<?php print($this->GetHtmlLabel('entityId',true)); ?> 
<input type="text" name="<?php print($entity_prefix); ?>_id" value="<?php print($entity->GetIdentifier()); ?>" size="60"/>
<br/><br/>
<!-- entity type -->
<?php print($this->GetHtmlLabel('entityType',true)); ?> 
<?php print(TpHtmlUtils::GetCombo($entity_prefix.'_type',$entity->GetType(),$this->GetOptions('entityType'))); ?>
<br/><br/>
<!-- entity names -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('entityName',true)); ?><br/>
<?php $cnt2 = 0; ?><?php $total2 = count($entity->GetNames()); ?>
<?php foreach ($entity->GetNames() as $name): ?><?php ++$cnt2; ?>
<input type="text" name="<?php print($entity_prefix); ?>_name_<?php print($cnt2); ?>" value="<?php print($name->GetValue()); ?>" size="45"/> 
<?php print(TpHtmlUtils::GetCombo($entity_prefix.'_name_lang_'.$cnt2,$name->GetLang(),$this->GetOptions('lang'))); ?> 
<?php if ($total2 > 1): ?><input type="submit" name="del_<?php print($entity_prefix); ?>_name_<?php print($cnt2); ?>" value="remove" onClick="javascript:saveScroll();"/><?php endif; ?>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_<?php print($entity_prefix); ?>_name" value="add name" onClick="javascript:saveScroll();"/>
</div>
<!-- entity roles -->
<?php print($this->GetHtmlLabel('entityRoles',true)); ?> 
<?php print(TpHtmlUtils::GetCheckboxes($entity_prefix.'_role',$related_entity->GetRoles(),$this->GetOptions('entityRoles'))); ?>
<br/><br/>
<!-- entity acronym -->
<?php print($this->GetHtmlLabel('acronym',true)); ?> 
<input type="text" name="<?php print($entity_prefix); ?>_acronym" value="<?php print($entity->GetAcronym()); ?>" size="20"/>
<br/>
<!-- entity description -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('entityDescription',false)); ?><br/>
<?php $cnt2 = 0; ?><?php $total2 = count($entity->GetDescriptions()); ?>
<?php foreach ($entity->GetDescriptions() as $description): ?><?php ++$cnt2; ?>
<textarea name="<?php print($entity_prefix); ?>_description_<?php print($cnt2); ?>" rows="3" cols="45" wrap="soft"><?php print($description->GetValue()); ?></textarea> 
<?php print(TpHtmlUtils::GetCombo($entity_prefix.'_description_lang_'.$cnt2,$description->GetLang(),$this->GetOptions('lang'))); ?> 
<?php if ($total2 > 1): ?><input type="submit" name="del_<?php print($entity_prefix); ?>_description_<?php print($cnt2); ?>" value="remove" onClick="javascript:saveScroll();"/><?php endif; ?>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_<?php print($entity_prefix); ?>_description" value="add description" onClick="javascript:saveScroll();"/>
</div>
<!-- entity address -->
<?php print($this->GetHtmlLabel('address',true)); ?>
<br/>
<textarea name="<?php print($entity_prefix); ?>_address" rows="2" cols="<?php print($textarea_cols); ?>" wrap="soft"><?php print($entity->GetAddress()); ?></textarea> 
<br/>
<br/>
<?php print($this->GetHtmlLabel('regionCode',false)); ?> 
<input type="text" name="<?php print($entity_prefix); ?>_regionCode" value="<?php print($entity->GetRegionCode()); ?>" size="5"/>
<?php print($this->GetHtmlLabel('countryCode',false)); ?> 
<?php print(TpHtmlUtils::GetCombo($entity_prefix.'_countryCode',$entity->GetCountryCode(),$this->GetOptions('countryCodes'))); ?>
<br/>
<br/>
<?php print($this->GetHtmlLabel('zipCode',false)); ?> 
<input type="text" name="<?php print($entity_prefix); ?>_zipCode" value="<?php print($entity->GetZipCode()); ?>" size="10"/>
<!-- entity coordinates -->
<?php print($this->GetHtmlLabel('longitude',false)); ?> 
<input type="text" name="<?php print($entity_prefix); ?>_longitude" value="<?php print($entity->GetLongitude()); ?>" size="12"/>
<?php print($this->GetHtmlLabel('latitude',false)); ?> 
<input type="text" name="<?php print($entity_prefix); ?>_latitude" value="<?php print($entity->GetLatitude()); ?>" size="12"/>
<br/>
<br/>
<!-- entity logoURL -->
<table width="100%">
<tr>
<td width="35%">
<?php print($this->GetHtmlLabel('logoURL',false)); ?>
</td>
<td width="65%"> 
<input type="text" name="<?php print($entity_prefix); ?>_logoURL" value="<?php print($entity->GetLogoUrl()); ?>" size="50"/>
<br/>
</td>
</tr>
<tr>
<td>
<!-- entity related information -->
<?php print($this->GetHtmlLabel('relatedInformation',false)); ?> 
</td>
<td>
<input type="text" name="<?php print($entity_prefix); ?>_relatedInformation" value="<?php print($entity->GetRelatedInformation()); ?>" size="50"/>
<br/>
</td>
</tr>
</table>
<!-- related contacts -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('relatedContacts',true)); ?><br/>
<?php $cnt3 = 0; ?><?php $total3 = count($entity->GetRelatedContacts()); ?>
<?php foreach ($entity->GetRelatedContacts() as $related_contact): ?><?php ++$cnt3; $contact = $related_contact->GetContact(); $contact_prefix = $entity_prefix.'_contact_'.$cnt3; ?>
<div class="box1" align="left">
<input type="hidden" name="<?php print($contact_prefix); ?>" value=""/>
<!-- contact full name -->
<?php print($this->GetHtmlLabel('fullName',true)); ?> 
<input type="text" name="<?php print($contact_prefix); ?>_fullname" value="<?php print($contact->GetFullName()); ?>" size="60"/>
<br/><br/>
<!-- contact roles -->
<?php print($this->GetHtmlLabel('contactRoles',true)); ?> 
<?php print(TpHtmlUtils::GetCheckboxes($contact_prefix.'_role',$related_contact->GetRoles(),$this->GetOptions('contactRoles'))); ?>
<br/>
<!-- contact titles -->
<div class="box2" align="left">
<?php print($this->GetHtmlLabel('contactTitle',false)); ?><br/>
<?php $cnt4 = 0; ?>
<?php foreach ($contact->GetTitles() as $title): ?><?php ++$cnt4; ?>
<input type="text" name="<?php print($contact_prefix); ?>_title_<?php print($cnt4); ?>" value="<?php print($title->GetValue()); ?>" size="30"/> 
<?php print(TpHtmlUtils::GetCombo($contact_prefix.'_title_lang_'.$cnt4,$title->GetLang(),$this->GetOptions('lang'))); ?> 
<input type="submit" name="del_<?php print($contact_prefix); ?>_title_<?php print($cnt4); ?>" value="remove" onClick="javascript:saveScroll();"/>
<br/>
<?php endforeach; ?>
<input type="submit" name="add_<?php print($contact_prefix); ?>_title" value="add title" onClick="javascript:saveScroll();"/>
</div>
<!-- contact e-mail -->
<table width="100%">
<tr>
<td width="20%">
<?php print($this->GetHtmlLabel('email',true)); ?> 
</td>
<td width="80%">
<input type="text" name="<?php print($contact_prefix); ?>_email" value="<?php print($contact->GetEmail()); ?>" size="40"/>
<br/>
</td>
</tr>
<tr>
<td>
<!-- contact telephone -->
<?php print($this->GetHtmlLabel('telephone',false)); ?> 
</td>
<td>
<input type="text" name="<?php print($contact_prefix); ?>_telephone" value="<?php print($contact->GetTelephone()); ?>" size="40"/>
</td>
</tr>
</table>
<!-- related contacts footer -->
<?php if ($total3 > 1): ?>
<br/><input type="submit" name="del_<?php print($contact_prefix); ?>" value="remove contact" onClick="javascript:saveScroll();"/><?php endif; ?>
</div>
<?php endforeach; ?>
<br/>
<input type="submit" name="add_<?php print($entity_prefix); ?>_contact" value="add related contact" onClick="javascript:saveScroll();"/>
</div>

<!-- related entities footer -->
<?php if ($total > 1): ?>
<br/><input type="submit" name="del_<?php print($entity_prefix); ?>" value="remove entity" onClick="javascript:saveScroll();"/><?php endif; ?>
</div>
<?php endforeach; ?>
<br/>
<input type="submit" name="add_entity" value="add related entity" onClick="javascript:saveScroll();"/>
</div>

</div>
<p class="tip"><?php print(TP_MANDATORY_FIELD_FLAG); ?>Indicates mandatory fields</p>
<!-- end of MetadataForm -->
