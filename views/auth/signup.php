<?php $this->layout()->extend('auth'); ?>
<?php $this->layout()->startBlock('title'); ?>
<?php echo $this->translate('Sign up'); ?>
<?php $this->layout()->endBlock(); ?>

<?php $this->layout()->startBlock('content'); ?>
<form method="post" action="/signup" class="w-full max-w-sm rounded-lg bg-white p-8 shadow-md">
    <?php if ($error ?? null): ?>
        <div class="rounded border border-red-700 bg-red-100 p-4 mb-4 text-sm text-red-700">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <label for="firstname" class="mb-1 block text-sm"><?php echo $this->translate('Firstname'); ?></label>
    <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($firstname ?? '', ENT_QUOTES); ?>" required autofocus class="mb-4 w-full rounded border border-gray-300 p-2 focus:border-blue-600 focus:outline-2 focus:outline-blue-600">
    <label for="lastname" class="mb-1 block text-sm"><?php echo $this->translate('Lastname'); ?></label>
    <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($lastname ?? '', ENT_QUOTES); ?>" required autofocus class="mb-4 w-full rounded border border-gray-300 p-2 focus:border-blue-600 focus:outline-2 focus:outline-blue-600">
    <label for="email" class="mb-1 block text-sm"><?php echo $this->translate('Email'); ?></label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES); ?>" required class="mb-4 w-full rounded border border-gray-300 p-2 focus:border-blue-600 focus:outline-2 focus:outline-blue-600">
    <label for="password" class="mb-1 block text-sm"><?php echo $this->translate('Password'); ?></label>
    <input type="password" id="password" name="password" minlength="8" required class="mb-4 w-full rounded border border-gray-300 p-2 focus:border-blue-600 focus:outline-2 focus:outline-blue-600">
    <button type="submit" class="w-full cursor-pointer rounded bg-blue-600 px-4 py-2.5 text-white hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"><?php echo $this->translate('Create account'); ?></button>
    <p class="mt-4 text-center text-sm"><a href="<?php echo $this->route('auth-signin'); ?>" class="text-blue-700 underline hover:text-blue-800"><?php echo $this->translate('Already have an account? Sign in'); ?></a></p>
</form>
<?php $this->layout()->endBlock(); ?>
