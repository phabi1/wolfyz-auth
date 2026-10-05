<?php
return array_merge(
    require __DIR__ . '/routes/auth.php',
    require __DIR__ . '/routes/oidc.php',
    require __DIR__ . '/routes/user.php',
);