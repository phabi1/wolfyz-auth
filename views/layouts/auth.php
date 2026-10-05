<?php
$title = $this->layout()->block('title', 'Wolf Auth');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>
    <?php echo $this->vite('assets/js/index.js'); ?>
</head>

<body class="flex min-h-screen items-center justify-center bg-gray-100 p-4 font-sans text-gray-900">
    <div class="w-full md:w-100">
        <img src="<?php echo $this->asset('img/logo.png'); ?>" alt="Logo" class="mb-4 mx-auto h-20 w-20">
        <h1 class="mb-4 text-center text-2xl font-semibold"><?php echo htmlspecialchars($title); ?></h1>
        <?php echo $this->layout()->block('content'); ?>
    </div>
</body>

</html>