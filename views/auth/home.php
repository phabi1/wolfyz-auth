<?php $this->layout()->extend('default'); ?>
<?php $this->layout()->startBlock('title'); ?>
<?php echo $this->translate('Welcome'); ?>
<?php $this->layout()->endBlock(); ?>
<?php $this->layout()->startBlock('lang'); ?>fr<?php $this->layout()->endBlock(); ?>

<?php $this->layout()->startBlock('content'); ?>
<main class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-md sm:p-10">
    <header class="text-center">
        <img src="<?php echo htmlspecialchars($this->asset('img/logo.png'), ENT_QUOTES, 'UTF-8'); ?>" alt="Roller Les Loups" class="mx-auto mb-6 h-20 w-20">
        <h1 class="mb-4 text-3xl font-semibold"><?php echo $this->translate('Welcome'); ?></h1>
        <p class="text-gray-600"><?php echo $this->translate('Your account for services.'); ?></p>
    </header>

    <nav aria-label="<?php echo htmlspecialchars($this->translate('Account navigation'), ENT_QUOTES, 'UTF-8'); ?>" class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
        <?php if ($isLoggedIn): ?>
            <a href="<?php echo $this->route('user-profile'); ?>" class="rounded bg-blue-600 px-6 py-3 text-center font-medium text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $this->translate('View my profile'); ?></a>
            <a href="<?php echo $this->route('auth-signout'); ?>" class="rounded border border-gray-300 px-6 py-3 text-center font-medium hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $this->translate('Sign Out'); ?></a>
        <?php else: ?>
            <a href="<?php echo $this->route('auth-signin'); ?>" class="rounded bg-blue-600 px-6 py-3 text-center font-medium text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $this->translate('Sign in'); ?></a>
            <a href="<?php echo $this->route('auth-signup'); ?>" class="rounded border border-gray-300 px-6 py-3 text-center font-medium hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $this->translate('Create account'); ?></a>
        <?php endif; ?>
    </nav>

    <?php if (!$isLoggedIn): ?>
        <p class="mt-6 text-center text-sm"><a href="<?php echo $this->route('auth-password-forgot'); ?>" class="text-blue-700 underline hover:text-blue-800"><?php echo $this->translate('Forgot password?'); ?></a></p>
    <?php endif; ?>
</main>
<?php $this->layout()->endBlock(); ?>
