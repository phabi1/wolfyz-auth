<?php

namespace App\User\Controller;

use App\Core\Http\RedirectResponse;
use App\Core\Mvc\Controller\AbstractController;

class ProfileController extends AbstractController
{
    public function indexAction()
    {
        $authenticationService = $this->getService('auth.authentication');
        if (!$authenticationService->isLoggedIn()) {
            return new RedirectResponse('/signin');
        }
        return $this->render('user/profile/index');
    }
}