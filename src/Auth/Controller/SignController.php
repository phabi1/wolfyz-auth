<?php

namespace App\Auth\Controller;

use App\Core\Mvc\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SignController extends AbstractController
{
    public function signinAction(Request $request)
    {
        $fields = [
            'identity' => '',
            'password' => ''
        ];

        if ($request->isMethod('POST')) {

            $fields['identity'] = $request->request->get('identity', '');
            $fields['password'] = $request->request->get('password', '');

            $useCaseBus = $this->getService('use-case-bus');
            $useCaseBus->execute('auth.sign-in', $fields);

            $session = $this->getService('session');
            if ($session->has('oauth2_redirect_uri')) {
                $url = $session->get('oauth2_redirect_uri', '/');
                $session->remove('oauth2_redirect_uri');
                return new RedirectResponse($url);
            } else {
                return $this->redirectToRoute('index');
            }
        }

        $signupUrl = $this->getService('router-generator')->generate('signup');

        $data = array_merge($fields, ['signup_url' => $signupUrl]);
        return $this->render('auth/signin', $data);
    }

    public function signupAction(Request $request)
    {
        $fields = [
            'email' => '',
            'password' => '',
            'confirm_password' => '',
            'firstname' => '',
            'lastname' => '',

        ];
        if ($request->isMethod('POST')) {
            $fields['email'] = $request->request->get('email', '');
            $fields['password'] = $request->request->get('password', '');
            $fields['confirm_password'] = $request->request->get('confirm_password', '');
            $fields['firstname'] = $request->request->get('firstname', '');
            $fields['lastname'] = $request->request->get('lastname', '');

            $useCaseBus = $this->getService('use-case-bus');
            $useCaseBus->execute('auth.sign-up', $fields);

            $session = $this->getService('session');
            $session = $this->getService('session');
            if ($session->has('oauth2_redirect_uri')) {
                $url = $session->get('oauth2_redirect_uri', '/');
                $session->remove('oauth2_redirect_uri');
                return new RedirectResponse($url);
            } else {
                return $this->redirectToRoute('index');
            }
        }
        return $this->render('auth/signup', $fields ?? []);
    }

    public function signoutAction(Request $request)
    {
        $this->getService('auth.authenticator')->logout();
        return $this->redirectToRoute('index');
    }
}