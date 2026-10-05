<?php

namespace App\Oidc\Controller;

use App\Core\Http\JsonResponse;
use App\Core\Http\Psr7;
use App\Core\Http\Psr7Response;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\RedirectResponse;
use App\Core\Mvc\Controller\ApiController;
use League\OAuth2\Server\Exception\OAuthServerException;


class AuthController extends ApiController
{
    public function authorizeAction(Request $request): ?Response
    {
        $server = $this->getService('oidc.server')->authorizationServer();
        $psrRequest = Psr7::requestFromGlobals();
        $authenticationService = $this->getService('auth.authentication');
        $session = $this->getService('session');

        try {
            $authRequest = $server->validateAuthorizationRequest($psrRequest);
        } catch (OAuthServerException $e) {
            return new Psr7Response($e->generateHttpResponse(Psr7::blankResponse()));
        }

        $redirectUri = $psrRequest->getServerParams()['REQUEST_URI'] ?? '/';

        if (!$authenticationService->isLoggedIn()) {
            $session->set('pending_authorize', $redirectUri);

            return new RedirectResponse('/signin');
        }

        $identity = $authenticationService->getIdentity();
        $user = $this->getService('oidc.server.repository.user')->getUserEntityByIdentifier($identity->getId());

        if (!$user) {
            $authenticationService->logout();
            $session->set('pending_authorize', $redirectUri);

            return new RedirectResponse('/signin');
        }


        $authRequest->setUser($user);
        $authRequest->setAuthorizationApproved(true);

        \App\Oidc\Server\NonceContext::set($psrRequest->getQueryParams()['nonce'] ?? null);

        try {
            $response = $server->completeAuthorizationRequest($authRequest, Psr7::blankResponse());
        } catch (OAuthServerException $e) {
            return new Psr7Response($e->generateHttpResponse(Psr7::blankResponse()));
        }

        return new Psr7Response($response);
    }

    public function tokenAction(Request $request): ?Response
    {
        $server = $this->getService('oidc.server')->authorizationServer();
        $psrRequest = Psr7::requestFromGlobals();

        try {
            $response = $server->respondToAccessTokenRequest($psrRequest, Psr7::blankResponse());
        } catch (OAuthServerException $e) {
            return new Psr7Response($e->generateHttpResponse(Psr7::blankResponse()));
        }

        return new Psr7Response($response);
    }

    public function userinfoAction(Request $request): ?Response
    {
        if (!in_array($request->method, ['GET', 'POST'], true)) {
            return new JsonResponse(
                ['error' => 'invalid_request', 'error_description' => 'Only GET and POST are supported.'],
                405,
                ['Allow' => 'GET, POST']
            );
        }

        $psrRequest = Psr7::requestFromGlobals();
        try {
            $data = $this->getService('oidc.userinfo')->build($psrRequest);
        } catch (OAuthServerException $e) {
            $challenge = 'Bearer';
            if ($psrRequest->getHeaderLine('Authorization') !== '') {
                $challenge .= ' error="' . $e->getErrorType() . '"';
            }
            if ($e->getErrorType() === 'insufficient_scope') {
                $challenge .= ', scope="openid"';
            }

            return new JsonResponse($e->getPayload(), $e->getHttpStatusCode(), [
                'WWW-Authenticate' => $challenge,
            ]);
        }

        return new JsonResponse($data);
    }

    public function introspectAction(Request $request): ?Response
    {
        $introspection = $this->getService('oidc.introspection');
        $data = $introspection->build();
        return new JsonResponse($data);
    }

    public function jwksAction(Request $request): ?Response
    {
        $parameters = $this->getService('parameters');
        $jwks = $this->getService('oidc.jwks');

        $paths = [
            $parameters->get('oidc.public_key'),
        ];
        $keys = [];
        foreach ($paths as $publicKeyPath) {
            $keys[] = $jwks->toJwk($publicKeyPath);
        }
        $data = [
            'keys' => $keys,
        ];
        return new JsonResponse($data);
    }
}