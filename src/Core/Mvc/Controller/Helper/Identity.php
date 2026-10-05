<?php

namespace App\Core\Mvc\Controller\Helper;

use App\Core\Security\Identity as IdentityModel;

class Identity {
    private $authenticator;

    public function __invoke() {
        return new IdentityModel(1);
    }
}