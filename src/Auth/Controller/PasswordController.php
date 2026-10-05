<?php

namespace App\Auth\Controller;

use App\Auth\Password\PasswordResetMailException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Mvc\Controller\AbstractController;

class PasswordController extends AbstractController
{
    public function forgotAction(Request $request): Response
    {

        $data = ['email' => '', 'error' => '', 'message' => '', 'csrf' => $this->csrfToken()->generate()];

        if ($request->method === 'POST') {
            $data['email'] = trim($request->body->get('email') ?? '');
            $csrf = $request->body->get('csrf');
            if (!$this->csrfToken()->validate($csrf)) {
                $data['error'] = $this->translate('Your session has expired. Please try again.');

            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $data['error'] = $this->translate('Please provide a valid email address.');
            } else {
                try {
                    $this->getService('auth.password-reset')->requestReset(
                        $data['email'],
                        (string) $this->getService('parameters')->get('oauth2.issuer'),
                    );
                    $data['message'] = $this->translate('If an account exists with this email, you will receive a password reset link.');
                } catch (PasswordResetMailException) {
                    $data['error'] = $this->translate('Password reset email is currently unavailable. Please try again later.');
                }
            }
        }

        return $this->render('auth/password-forgot', $data);
    }

    public function resetAction(Request $request): Response
    {
        $token = $request->method === 'POST'
            ? $request->body->get('token') : ($request->query->get('token') ?? '');
        $token = is_string($token) ? $token : '';
        $service = $this->getService('auth.password-reset');
        $data = [
            'token' => $token,
            'error' => '',
            'message' => '',
            'valid' => $service->isValidToken($token),
            'csrf' => $this->csrfToken()->generate(),
        ];

        if (!$data['valid']) {
            $data['error'] = $this->translate('This password reset link is invalid or has expired.');
            return $this->render('auth/password-reset', $data);
        } elseif ($request->method === 'POST') {
            $password = $request->body->get('password');
            if (!$this->csrfToken()->validate($request->body->get('csrf'))) {
                $data['error'] = $this->translate('Your session has expired. Please try again.');

            } elseif (strlen($password) < 8 || strlen($password) > 72) {
                $data['error'] = $this->translate('Password must contain between 8 and 72 bytes.');
            } elseif ($password !== $request->body->get('password_confirmation')) {
                $data['error'] = $this->translate('The passwords do not match.');
            } elseif (!$service->resetPassword($token, $password)) {
                $data['valid'] = false;
                $data['error'] = $this->translate('This password reset link is invalid or has expired.');
            } else {
                $data['valid'] = false;
                $data['token'] = '';
                $data['message'] = $this->translate('Your password has been reset. You can now sign in.');
            }
        }

        return $this->render('auth/password-reset', $data);
    }
}
