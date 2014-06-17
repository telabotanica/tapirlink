
      <!-- ============= begin WIZARD HEADER ============= -->

      <!-- ================ begin TOP MENU =============== -->
      <span class="section"><?php if ($this->InWizardMode()): ?>New resource<?php else: ?><?php print($this->GetResourceId()); ?><?php endif; ?></span>
      <br/>
      <br/>
      <table align="center" width="<?php if ($this->InWizardMode()): ?>60<?php else: ?>90<?php endif; ?>%" cellspacing="1" cellpadding="1" bgcolor="#999999">
        <tr>
          <?php if ($this->InWizardMode()): ?>
          <td align="center" valign="middle" width="100%" bgcolor="#f5f5ff" class="label">step&nbsp;<?php print($this->GetStep()); ?>:&nbsp;&nbsp;&nbsp;<?php print($this->GetLabel()); ?></td>
          <?php else: ?><?php $config_manager = new TpConfigManager(); ?><?php for ($i = 1; $i <= $this->GetNumSteps(); ++$i): ?><?php $wiz = $config_manager->GetWizardPage($i); ?><?php $bg_color = ($this->GetStep() == $i) ? '#ffffee' : '#f5f5ff'; ?><?php $class = ($this->GetStep() == $i) ? 'label' : 'text'; ?> 
          <td align="center" valign="middle" width="<?php print(100/$this->GetNumSteps()); ?>%" bgcolor="<?php print($bg_color); ?>"><a href="<?php print($_SERVER['PHP_SELF']); ?>?form=<?php print($i); ?>&amp;resource=<?php print($this->GetResourceId()); ?>" class="<?php print($class); ?>"><?php print($wiz->GetLabel()); ?></a></td><?php endfor; ?>
          <?php endif; ?>
        </tr>
      </table>
      <!-- ================= end TOP MENU ================ -->

      <?php if ($this->mMessage) printf("\n<br/><span class=\"msg\">%s</span>", nl2br($this->mMessage)); ?><?php if (count($errors)) printf("\n<br/><span class=\"error\">%s</span>", nl2br(implode('<br/>', $errors))); ?>

      <form name="wizard" action="<?php print($_SERVER['PHP_SELF']); ?>" method="post">
      <input type="hidden" name="resource" value="<?php print(TpUtils::GetVar('resource')); ?>"/>
      <input type="hidden" name="form" value="<?php print($this->GetStep()); ?>"/>
      <input type="hidden" name="refresh" value=""/>
      <input type="hidden" name="scroll"/>

      <!-- ============= end WIZARD HEADER ============== -->

