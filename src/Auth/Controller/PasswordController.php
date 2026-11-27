<?php

namespace App\Auth\Controller;

use App\Auth\Password\PasswordResetMailException;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Mvc\Controller\AbstractController;

class PasswordController extends AbstractController
{
    private const ALLOWED_METHODS = ['GET', 'HEAD', 'POST'];

    public function forgotAction(Request $request): Response
    {
        if (!in_array($request->method, self::ALLOWED_METHODS, true)) {
            return $this->methodNotAllowed();
        }

        $data = ['email' => '', 'error' => '', 'message' => '', 'csrf' => $this->csrfToken()->generate()];
        $status = 200;

        if ($request->method === 'POST') {
            $data['email'] = trim((string) ($request->body->get('email') ?? ''));

            if (!$this->isValidCsrf($request)) {
                $status = 403;
                $data['error'] = $this->translate('Your session has expired. Please try again.');
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $status = 400;
                $data['error'] = $this->translate('Please provide a valid email address.');
            } elseif (!$this->getService('mailer')->isConfigured()) {
                // Checked before creating a token: no reset link can be delivered.
                $status = 503;
                $data['error'] = $this->translate('Password reset email is currently unavailable. Please try again later.');
            } else {
                try {
                    $this->getService('auth.password-reset')->requestReset($data['email']);
                    $data['message'] = $this->translate('If an account exists with this email, you will receive a password reset link.');
                } catch (PasswordResetMailException) {
                    $status = 503;
                    $data['error'] = $this->translate('Password reset email is currently unavailable. Please try again later.');
                }
            }
        }

        return $this->respond('auth/password-forgot', $data, $status);
    }

    public function resetAction(Request $request): Response
    {
        if (!in_array($request->method, self::ALLOWED_METHODS, true)) {
            return $this->methodNotAllowed();
        }

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
            return $this->respond('auth/password-reset', $data, 400);
        }

        $status = 200;
        if ($request->method === 'POST') {
            $password = $request->body->get('password');
            $password = is_string($password) ? $password : '';

            if (!$this->isValidCsrf($request)) {
                $status = 403;
                $data['error'] = $this->translate('Your session has expired. Please try again.');
            } elseif (strlen($password) < 8 || strlen($password) > 72) {
                $status = 400;
                $data['error'] = $this->translate('Password must contain between 8 and 72 bytes.');
            } elseif ($password !== $request->body->get('password_confirmation')) {
                $status = 400;
                $data['error'] = $this->translate('The passwords do not match.');
            } elseif (!$service->resetPassword($token, $password)) {
                $status = 400;
                $data['valid'] = false;
                $data['error'] = $this->translate('This password reset link is invalid or has expired.');
            } else {
                $data['valid'] = false;
                $data['token'] = '';
                $data['message'] = $this->translate('Your password has been reset. You can now sign in.');
            }
        }

        return $this->respond('auth/password-reset', $data, $status);
    }

    private function isValidCsrf(Request $request): bool
    {
        $csrf = $request->body->get('csrf');
        return is_string($csrf) && $csrf !== '' && $this->csrfToken()->validate($csrf);
    }

    /**
     * The reset link carries its token in the URL: never cache these pages
     * nor leak the URL through the Referer header.
     */
    private function respond(string $template, array $data, int $status): Response
    {
        $html = $this->getService('view')->render($template, $data);

        return new Response($html, $status, [
            'Cache-Control' => 'no-store',
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function methodNotAllowed(): Response
    {
        return new Response('', 405, ['Allow' => 'GET, POST']);
    }
}
