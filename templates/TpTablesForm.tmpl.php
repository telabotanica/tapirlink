
<!-- beginning of TablesForm -->
<br/>
           <div class="box2" align="left">
           <span class="label">Root table and key field: </span><?php print(TpHtmlUtils::GetCombo('root',$this->GetRoot(),$this->GetOptions('AllTablesAndColumns'), false, false, "document.forms[1].refresh.value='root';document.forms[1].submit()")); ?><br/>
           <?php if ( $this->GetRoot() and ! $this->mDetectedInconsistency ): ?>
             <?php print($this->GetJoins($this->GetRootTable())); ?>
             <br/><br/>
             <span class="label">New join: </span><?php print(TpHtmlUtils::GetCombo('from', TpUtils::GetVar('from', '0'), $this->GetOptions('TablesAndColumnsInside'))); ?>&nbsp;<?php print(TpHtmlUtils::GetCombo('to', TpUtils::GetVar('to', '0'), $this->GetOptions('TablesAndColumnsOutside'))); ?>&nbsp;<input type="submit" name="addjoin" value="add"/>
           <?php endif; ?>
           </div>

<br/>
<!-- end of TablesForm -->
