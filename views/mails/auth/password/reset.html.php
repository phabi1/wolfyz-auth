<?php $this->layout()->extend('mail/html'); ?>
<?php $this->layout()->startBlock('content'); ?>
<p><?php echo $this->translate('A password reset was requested for your Wolf Auth account.'); ?></p>
<p><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $this->translate('Reset your password'); ?></a></p>
<p><?php echo $this->translate('This link expires in one hour and can be used only once.'); ?></p>
<p><?php echo $this->translate('If you did not request this reset, you can ignore this email.'); ?></p>
<?php $this->layout()->endBlock(); ?>
