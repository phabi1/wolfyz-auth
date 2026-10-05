<?php
$title = $this->layout()->block('title', 'Wolf Auth');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($this->layout()->block('lang', 'en'), ENT_QUOTES, 'UTF-8'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title); ?></title>
    <?php echo $this->vite('assets/js/index.js'); ?>
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-100 p-4 font-sans text-gray-900">
    <?php echo $this->layout()->block('content'); ?>
</body>
</html>
