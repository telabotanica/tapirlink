

      <!-- ============ begin WIZARD FOOTER ============= -->

<?php if ($this->mStep): ?>
 <?php if ($this->mWizardMode): ?>
  <?php if ($this->mStep > 1): ?>
      <input type="submit" name="abort" value="abort" onClick="return confirmRemoval()">
      &nbsp;&nbsp;&nbsp;&nbsp;
  <?php endif; ?>
  <?php if ($this->ReadyToProceed()): ?>
    <?php if ($this->mStep < $this->mNumSteps): ?>
      <input type="submit" name="next" value="next step &gt;&gt;">
    <?php else: ?>
      <input type="submit" name="save" value="save new resource">
    <?php endif; ?>
  <?php endif; ?>
 <?php else: ?>
      <input type="submit" name="update" value="save changes">
 <?php endif; ?>
<?php endif; ?>

      <br/>
      </form>

      <!-- ============= end WIZARD FOOTER ============== -->
