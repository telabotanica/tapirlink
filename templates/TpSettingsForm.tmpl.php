
          <!-- beginning of SettingsForm -->
          <br/>
          <table align="center" width="92%" cellspacing="1" cellpadding="1" bgcolor="#999999">
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('max_repetitions',true)); ?></td><td align="left" valign="middle" width="60%"><input type="text" name="max_repetitions" value="<?php print($r_settings->GetMaxelementRepetitions()); ?>" size="10"></td></tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('max_levels',true)); ?></td><td align="left" valign="middle" width="60%"><input type="text" name="max_levels" value="<?php print($r_settings->GetMaxElementLevels()); ?>" size="10"></td></tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('log_only',true)); ?></td><td align="left" valign="middle" width="60%"><?php print(TpHtmlUtils::GetCombo('log_only',$r_settings->GetLogOnly(),$this->GetOptions('logonly'))); ?><br/></td></tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('case_sensitive_equals',true)); ?></td><td align="left" valign="middle" width="60%"><?php print(TpHtmlUtils::GetCombo('case_sensitive_equals',($r_settings->GetCaseSensitiveInEquals())?'true':'false',$this->GetOptions('boolean'))); ?><br/></td></tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('case_sensitive_like',true)); ?></td><td align="left" valign="middle" width="60%"><?php print(TpHtmlUtils::GetCombo('case_sensitive_like',($r_settings->GetCaseSensitiveInLike())?'true':'false',$this->GetOptions('boolean'))); ?><br/></td></tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('date_last_modified',true)); ?></td><td align="left" valign="middle" width="60%"><span class="label">Dynamically from field: </span><br/><?php print(TpHtmlUtils::GetCombo('modifier',$r_settings->GetModifier(),$this->GetOptions('tables_and_columns'))); ?><br/><br/><span class="label">Or from fixed value:</span><br/><input type="text" name="modified" value="<?php print($r_settings->GetModified()); ?>" size="30">&nbsp;<input type="submit" name="set_modified" value="set to now"/><br/></td></tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label"><?php print($this->GetHtmlLabel('inventory_templates',false)); ?></td>
            <td align="left" valign="middle" width="60%">
             <table border="0" align="center" width="100%">
               <tr>
                 <td align="left" valign="middle" width="30%" class="label">alias</td>
                 <td align="left" valign="middle" width="70%" class="label">location</td>
               </tr><?php $i = 0; ?>
               <?php foreach($r_settings->GetInventoryTemplates() as $alias => $loc): ?>
               <tr><?php ++$i; ?>
                 <td align="left" valign="middle" width="30%" class="label"><input type="text" name="inv_alias_<?php print($i); ?>" value="<?php print($alias); ?>" size="10"></td>
                 <td align="left" valign="middle" width="70%" class="label"><input type="text" name="inv_loc_<?php print($i); ?>" value="<?php print($loc); ?>" size="30"></td>
               </tr>
               <?php endforeach; ?>
               <tr>
                 <td align="left" valign="middle" width="30%" class="label"><input type="text" name="inv_alias_new" value="<?php print(TpUtils::GetVar('inv_alias_new','')); ?>" size="10"></td>
                 <td align="left" valign="middle" width="70%" class="label"><input type="text" name="inv_loc_new" value="<?php print(TpUtils::GetVar('inv_loc_new','')); ?>" size="30">&nbsp;<input type="submit" name="add_inv_template" value="add"/></td>
               </tr>
             </table>
            </td>
           </tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label"><?php print($this->GetHtmlLabel('search_templates',false)); ?></td>
            <td align="left" valign="middle" width="60%">
             <table border="0" align="center" width="100%">
               <tr>
                 <td align="left" valign="middle" width="30%" class="label">alias</td>
                 <td align="left" valign="middle" width="70%" class="label">location</td>
               </tr><?php $i = 0; ?>
               <?php foreach($r_settings->GetSearchTemplates() as $alias => $loc): ?>
               <tr><?php ++$i; ?>
                 <td align="left" valign="middle" width="30%" class="label"><input type="text" name="search_alias_<?php print($i); ?>" value="<?php print($alias); ?>" size="10"></td>
                 <td align="left" valign="middle" width="70%" class="label"><input type="text" name="search_loc_<?php print($i); ?>" value="<?php print($loc); ?>" size="30"></td>
               </tr>
               <?php endforeach; ?>
               <tr>
                 <td align="left" valign="middle" width="30%" class="label"><input type="text" name="search_alias_new" value="<?php print(TpUtils::GetVar('search_alias_new','')); ?>" size="10"></td>
                 <td align="left" valign="middle" width="70%" class="label"><input type="text" name="search_loc_new" value="<?php print(TpUtils::GetVar('search_loc_new','')); ?>" size="30">&nbsp;<input type="submit" name="add_search_template" value="add"/></td>
               </tr>
             </table>
            </td>
           </tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label"><?php print($this->GetHtmlLabel('pre_output_models',false)); ?></td>
            <td align="left" valign="middle" width="60%">
             <table border="0" align="center" width="100%">
               <tr>
                 <td align="left" valign="middle" width="30%" class="label">alias</td>
                 <td align="left" valign="middle" width="70%" class="label">location</td>
               </tr><?php $i = 0; ?>
               <?php foreach($r_settings->GetOutputModels() as $alias => $loc): ?>
               <tr><?php ++$i; ?>
                 <td align="left" valign="middle" width="30%" class="label"><input type="text" name="model_alias_<?php print($i); ?>" value="<?php print($alias); ?>" size="10"></td>
                 <td align="left" valign="middle" width="70%" class="label"><input type="text" name="model_loc_<?php print($i); ?>" value="<?php print($loc); ?>" size="30"></td>
               </tr>
               <?php endforeach; ?>
               <tr>
                 <td align="left" valign="middle" width="30%" class="label"><input type="text" name="model_alias_new" value="<?php print(TpUtils::GetVar('model_alias_new','')); ?>" size="10"></td>
                 <td align="left" valign="middle" width="70%" class="label"><input type="text" name="model_loc_new" value="<?php print(TpUtils::GetVar('model_loc_new','')); ?>" size="30">&nbsp;<input type="submit" name="add_output_model" value="add"/></td>
               </tr>
             </table>
            </td>
           </tr>
           <tr bgcolor="#ffffee"><td align="left" valign="middle" width="40%" class="label_required"><?php print($this->GetHtmlLabel('custom_output_models',true)); ?></td>
            <td align="left" valign="middle" width="60%"><?php print(TpHtmlUtils::GetRadio('custom_output_models',($r_settings->GetCustomOutputModelsAcceptance())?'true':'false',$this->GetOptions('custom_output_models'))); ?><br/></td>
           </tr>
          </table>
          <p class="tip"><?php print(TP_MANDATORY_FIELD_FLAG); ?>Indicates mandatory fields</p>
          <br/>
          <!-- end of SettingsForm -->
