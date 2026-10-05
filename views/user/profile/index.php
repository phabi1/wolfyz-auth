<?php
$this->layout()->extend('default');
?>
<?php
$this->layout()->startBlock('content');
?>
<main class="w-full max-w-sm rounded-lg bg-white p-8 shadow-md">
    <h1 class="mb-4 text-xl font-semibold"><?php echo $this->translate('User Profile'); ?></h1>
    <a href="/signout" class="text-blue-700 underline hover:text-blue-800"><?php echo $this->translate('Sign Out'); ?></a>
</main>
<?php $this->layout()->endBlock(); ?>