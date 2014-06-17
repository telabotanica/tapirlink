
      <!-- ============ begin UDDI FORM ============= -->
      <span class="section">UDDI Registration</span>
      <br/>
      <?php $this->Process(); ?>
      <?php $this->EchoErrors(); ?>
      <?php if ( count( $active_resources ) ): ?>
      <form name="uddi" action="<?php print($_SERVER['PHP_SELF']); ?>" method="post">
      <input type="hidden" name="uddi" value="1"/>
      <br/>
      <br/>
      <table width="90%" cellspacing="3" cellpadding="1" bgcolor="#FFFFFF">
        <tr>
          <td align="left">
            <span class="label">Registry name: </span>
            <input type="text" name="uddi_name" value="<?php print( TpUtils::GetVar('uddi_name', TP_UDDI_OPERATOR_NAME) ); ?>" size="10"/>
            &nbsp;&nbsp;
            <span class="label">Tmodel name: </span>
            <input type="text" name="tmodel_name" value="<?php print( TpUtils::GetVar('tmodel_name', TP_UDDI_TMODEL_NAME) ); ?>" size="10"/>
          </td>
        </tr>
        <tr>
          <td align="left">
            <span class="label">Inquiry URL: </span>
            <input type="text" name="inquiry_url" value="<?php print( TpUtils::GetVar('inquiry_url', TP_UDDI_INQUIRY_URL) ); ?>" size="50"/>
            &nbsp;&nbsp;
            <span class="label">Port: </span>
            <input type="text" name="inquiry_port" value="<?php print( TpUtils::GetVar('inquiry_port', TP_UDDI_INQUIRY_PORT) ); ?>" size="4"/>
          </td>
        </tr>
        <tr>
          <td align="left">
            <span class="label">Publish URL: </span>
            <input type="text" name="publish_url" value="<?php print( TpUtils::GetVar('publish_url', TP_UDDI_PUBLISH_URL) ); ?>" size="50"/>
            &nbsp;&nbsp;
            <span class="label">Port: </span>
            <input type="text" name="publish_port" value="<?php print( TpUtils::GetVar('publish_port', TP_UDDI_PUBLISH_PORT) ); ?>" size="4"/>
          </td>
        </tr>
        <tr>
          <td align="left">
            <span class="label">Active Resources: </span>
            <br/>
            <table width="100%" cellspacing="1" cellpadding="1" bgcolor="#999999">
            <?php foreach ($active_resources as $res): ?>
            <?php $main_title = $this->GetServiceMainTitle($res); ?><?php $main_business_name = $this->GetNameOfMainBusiness($res); ?>
             <tr>
              <td width="5%" align="center" bgcolor="#f5f5ff"><input type="checkbox" class="checkbox" name="resources[]" value="<?php print($res->GetCode()); ?>"<?php if (in_array($res->GetCode(), TpUtils::GetVar('resources', array()))): ?> checked="1"<?php endif; ?>/></td>
              <td width="10%" align="left" bgcolor="#f5f5ff"><?php print($res->GetCode()); ?></td>
              <td width="85%" align="left" bgcolor="#f5f5ff">Service name: <?php print($main_title->GetValue()); ?><br/>Business name: <?php print($main_business_name->GetValue()); ?></td>
             </tr>
            <?php endforeach; ?>
           </table>
          </td>
        </tr>
      </table>
      <br/>
      <br/>
      <input type="submit" name="register" value="register selected resources">
      <?php endif; ?>
      <br/>
      </form>
      <br/>
      <?php if (_DEBUG): ?>
<div align="left">
<pre>
<?php foreach ( $this->mInteractions as $operation => $pair ): ?>


<?php print($operation); ?>


REQUEST:
<?php print($pair['req']); ?>

RESPONSE:

<?php print($pair['resp']); ?>
<?php endforeach; ?>

</pre>
</div>
      <?php endif; ?>

      <!-- ============= end UDDI FORM ============== -->
