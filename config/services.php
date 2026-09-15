<?php

return array_merge(
    require __DIR__ . '/services/core.php',
    require __DIR__ . '/services/auth.php',
    require __DIR__ . '/services/user.php',
    require __DIR__ . '/services/oauth2.php'
);