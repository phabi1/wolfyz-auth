<?php $this->layout()->extend('auth'); ?>
<?php $this->layout()->startBlock('title'); ?>
<?php echo $this->translate('Sign in'); ?>
<?php $this->layout()->endBlock(); ?>

<?php $this->layout()->startBlock('content'); ?>
<form method="post" action="/signin" class="w-full max-w-sm rounded-lg bg-white p-8 shadow-md">
    <?php if ($error ?? null): ?>
        <p class="mb-4 text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <label for="email" class="mb-1 block text-sm"><?php echo $this->translate('Email'); ?></label>
    <input type="email" id="email" name="email" required autofocus
        class="mb-4 w-full rounded border border-gray-300 p-2 focus:border-blue-600 focus:outline-2 focus:outline-blue-600">
    <label for="password" class="mb-1 block text-sm"><?php echo $this->translate('Password'); ?></label>
    <input type="password" id="password" name="password" required
        class="mb-4 w-full rounded border border-gray-300 p-2 focus:border-blue-600 focus:outline-2 focus:outline-blue-600">
    <button type="submit"
        class="w-full cursor-pointer rounded bg-blue-600 px-4 py-2.5 text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $this->translate('Sign in'); ?></button>
    <p class="mt-4 text-center text-sm"><a href="<?php echo $this->route('auth-password-forgot'); ?>"
            class="text-blue-700 underline hover:text-blue-800"><?php echo $this->translate('Forgot password?'); ?></a></p>
    <p class="mt-4 text-center text-sm"><a href="<?php echo $this->route('auth-signup'); ?>"
            class="text-blue-700 underline hover:text-blue-800"><?php echo $this->translate('No account yet? Sign up'); ?></a></p>
</form>
<?php $this->layout()->endBlock(); ?>