<?php

namespace App\Auth\Controller;

use App\Core\Db\Exception\DuplicateEntryException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\RedirectResponse;
use App\Core\Mvc\Controller\AbstractController;

class SignController extends AbstractController
{
    public function signinAction(Request $request): Response
    {
        if ($this->checkIsAlreadyLoggedIn()) {
            return new RedirectResponse($this->redirectTarget());
        }

        $data = ['email' => '', 'error' => '', 'csrf' => $this->csrfToken()->generate()];

        if ($request->method === 'POST') {
            $csrf = (string) ($request->body->get('csrf') ?? '');
            if (!$this->csrfToken()->validate($csrf)) {
                $data['error'] = $this->translate('Invalid CSRF token.');

                return $this->render('auth/signin', $data);
            }
            $email = trim((string) ($request->body->get('email') ?? ''));
            $password = (string) ($request->body->get('password') ?? '');
            $data['email'] = $email;

            $authenticationService = $this->getService('auth.authentication');
            $res = $authenticationService->validate($email, $password);

            if ($res->valid) {
                $authenticationService->login($res->user->id);

                return new RedirectResponse($this->redirectTarget());
            }

            $data['error'] = $this->translate('Invalid email or password.');
        }

        return $this->render('auth/signin', $data);
    }

    public function signupAction(Request $request): Response
    {
        if ($this->checkIsAlreadyLoggedIn()) {
            return new RedirectResponse($this->redirectTarget());
        }

        $data = ['email' => '', 'name' => '', 'error' => '', 'csrf' => $this->csrfToken()->generate()];

        if ($request->method === 'POST') {
            $csrf = (string) ($request->body->get('csrf') ?? '');
            if (!$this->csrfToken()->validate($csrf)) {
                $data['error'] = $this->translate('Invalid CSRF token.');

                return $this->render('auth/signup', $data);
            }

            $email = trim((string) ($request->body->get('email') ?? ''));
            $password = (string) ($request->body->get('password') ?? '');
            $firstname = trim((string) ($request->body->get('firstname') ?? ''));
            $lastname = trim((string) ($request->body->get('lastname') ?? ''));
            $data['email'] = $email;
            $data['firstname'] = $firstname;
            $data['lastname'] = $lastname;

            try {
                $user = $this->useCaseBus('auth.register-user', [
                    'email' => $email,
                    'password' => $password,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                ]);
                $this->getService('auth.authentication')->login($user->id);
                return new RedirectResponse($this->redirectTarget());
            } catch (\App\Auth\Exception\EmailAlreadyExistsException $e) {
                $data['error'] = $this->translate('Email already exists');
            } catch (\Exception $e) {
                $data['error'] = $this->translate('Unexpected error');
            }
        }

        return $this->render('auth/signup', $data);
    }

    public function signoutAction(Request $request): Response
    {
        $this->getService('auth.authentication')->logout();

        $redirect = $request->query->get('post_logout_redirect_uri') ?? '/signin';

        return new RedirectResponse($redirect);
    }

    private function checkIsAlreadyLoggedIn(): bool
    {
        return !empty($this->getService('session')->get('user_id'));
    }

    private function redirectTarget(): string
    {
        $session = $this->getService('session');
        $target = $session->get('pending_authorize', '/');
        $session->remove('pending_authorize');

        return $target;
    }
}
