<?php $this->layout()->extend('mail/text'); ?>

<?php $this->layout()->startBlock('content'); ?>
<?php echo $this->translate('A password reset was requested for your Wolf Auth account.') . "\n\n"; ?>
<?php echo $this->translate('Reset your password using this link:') . "\n"; ?>
<?php echo $url . "\n"; ?>

<?php echo $this->translate('This link expires in one hour and can be used only once.') . "\n"; ?>
<?php echo $this->translate('If you did not request this reset, you can ignore this email.') . "\n"; ?>
<?php $this->layout()->endBlock(); ?>