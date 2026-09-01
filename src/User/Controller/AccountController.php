<?php

namespace App\User\Controller;

use App\Core\Mvc\Controller\AbstractController;

class AccountController extends AbstractController
{
    public function indexAction() {
        if (!$this->getService('auth.authenticator')->isAuthenticated()) {
            return $this->redirectToRoute('signin');
        }
        return $this->render('user/account/index');
    }
}