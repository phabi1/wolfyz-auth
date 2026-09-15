<?php

namespace App\OAuth2\Controller;

use App\Core\Mvc\Controller\ApiController;
use App\OAuth2\Entity\Repository\AccessTokenRepositoryInterface;
use App\OAuth2\Server\Entity\UserEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Nyholm\Psr7\Factory\Psr17Factory as NyholmFactory;

class AuthController extends ApiController
{
    public function authorizeAction(Request $request)
    {

        $server = $this->getService('oauth2.server');

        try {
            $nyholmFactory = new NyholmFactory();

            $psrHttpFactory = new PsrHttpFactory(
                $nyholmFactory, // ServerRequestFactoryInterface
                $nyholmFactory, // StreamFactoryInterface
                $nyholmFactory, // UploadedFileFactoryInterface
                $nyholmFactory  // ResponseFactoryInterface
            );

            $psrRequest = $psrHttpFactory->createRequest($request);

            $authRequest = $server->validateAuthorizationRequest($psrRequest);

            $authenticator = $this->getService('auth.authenticator');

            if (!$authenticator->isAuthenticated()) {
                $session = $this->getService('session');
                $session->set('oauth2_redirect_uri', $request->getUri());
                return $this->redirectToRoute('signin');
            }

            $authRequest->setUser(new UserEntity($authenticator->getIdentity()->getId()));

            $authRequest->setAuthorizationApproved(true);

            $psrResponse = new \Nyholm\Psr7\Response();

            $response = $server->completeAuthorizationRequest($authRequest, $psrResponse);

            $response = new RedirectResponse($response->getHeader('Location')[0] ?? '/');
            return $response;

        } catch (OAuthServerException $exception) {
            $response = $this->generateHttpResponseFromException($exception);
            $response = $this->withCors($response, $request);
            return $response;
        }
    }


    public function tokenAction(Request $request)
    {
        $server = $this->getService('oauth2.server');

        try {

            foreach ($request->attributes->all() as $key => $value) {
                if (!is_string($key)) {
                    $request->attributes->remove($key);
                    $request->attributes->set((string) 'a' . $key, $value);
                }
            }

            $nyholmFactory = new NyholmFactory();

            $psrHttpFactory = new PsrHttpFactory(
                $nyholmFactory, // ServerRequestFactoryInterface
                $nyholmFactory, // StreamFactoryInterface
                $nyholmFactory, // UploadedFileFactoryInterface
                $nyholmFactory  // ResponseFactoryInterface
            );

            $psrRequest = $psrHttpFactory->createRequest($request);

            $psrResponse = new \Nyholm\Psr7\Response();

            $psrResponse = $server->respondToAccessTokenRequest($psrRequest, $psrResponse);
            $response = $this->generateResponseFromPsr($psrResponse);
            return $response;

        } catch (OAuthServerException $exception) {
            $response = $this->generateHttpResponseFromException($exception);
            $response = $this->withCors($response, $request);
            return $response;
        } catch (\Throwable $exception) {
            $this->getService('watchdog')->error($exception->getMessage(), ['exception' => $exception->getTraceAsString()]);
            $response = new JsonResponse([
                'error' => 'server_error',
                'message' => 'Une erreur interne est survenue sur le serveur d\'autorisation.',
                'exception' => $exception->getMessage()
            ], 500);
            $response = $this->withCors($response, $request);
            return $response;
        }
    }

    public function userinfoAction(Request $request)
    {
        // $authorizationHeader = $request->headers->get('authorization');

        // $token = null;
        // if ($authorizationHeader && preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
        //     $token = $matches[1];
        // }

        // if (!$token) {
        //     $response = new JsonResponse([
        //         'error' => 'invalid_request',
        //         'message' => 'Le jeton d\'accès est manquant.'
        //     ], 400);
        //     $response = $this->withCors($response, $request);
        //     return $response;
        // }

        $entityManager = $this->getService('entity-manager');

        // /**
        //  * @var AccessTokenRepositoryInterface
        //  */
        // $accessTokenRepository = $entityManager->getRepository('oauth2.access_token');

        // $accessToken = $accessTokenRepository->findByToken($token);
        // if (!$accessToken) {
        //     $response = new JsonResponse([
        //         'error' => 'invalid_token',
        //         'message' => 'Le jeton d\'accès est invalide.'
        //     ], 401);
        //     $response = $this->withCors($response, $request);
        //     return $response;
        // }

        $userRepository = $entityManager->getRepository('user');
        // if ($accessToken->user_id === null) {
        //     $response = new JsonResponse([
        //         'error' => 'invalid_token',
        //         'message' => 'Le jeton d\'accès ne contient pas d\'identifiant utilisateur.'
        //     ], 401);
        //     $response = $this->withCors($response, $request);
        //     return $response;
        // }
    
        $user = $userRepository->findById(1);

        if (!$user) {
            $response = new JsonResponse([
                'error' => 'invalid_token',
                'message' => 'L\'utilisateur associé au jeton d\'accès est introuvable.'
            ], 401);
            $response = $this->withCors($response, $request);
            return $response;
        }

        $response = new JsonResponse([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
            ]
        ]);
        $response = $this->withCors($response, $request);

        return $response;
    }

    public function jwksAction(Request $request)
    {
        $publicKey = openssl_pkey_get_public(file_get_contents( APP_DIR . '/certs/oauth2-public.key'));
        $details = openssl_pkey_get_details($publicKey);
        $jwks = [
            'keys' => [
                [
                    'kty' => 'RSA',
                    'kid' => '1',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => rtrim(strtr(base64_encode($details['rsa']['n']), '+/', '-_'), '='),
                    'e' => rtrim(strtr(base64_encode($details['rsa']['e']), '+/', '-_'), '=')
                ]
            ]
        ];

        $response = new JsonResponse($jwks);
        $response = $this->withCors($response, $request);

        return $response;
    }

    public function introspectAction(Request $request)
    {
        $issuer = $this->getService('parameters')->get('oauth2.issuer', 'https://auth-wolfzy.docker.localhost');

        $payload = [
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/oauth2/authorize',
            'token_endpoint' => $issuer . '/oauth2/token',
            'session_endpoint' => $issuer . '/oauth2/session',
            'userinfo_endpoint' => $issuer . '/oauth2/userinfo',
            'jwks_uri' => $issuer . '/oauth2/jwks'
        ];

        $response = new JsonResponse($payload);
        $response = $this->withCors($response, $request);

        return $response;
    }

    private function generateResponseFromPsr($psrResponse)
    {
        $response = new Response();

        foreach ($psrResponse->getHeaders() as $header => $values) {
            $response->headers->set($header, is_array($values) ? implode(', ', $values) : $values);
        }

        $response->setContent((string) $psrResponse->getBody());
        $response->setStatusCode($psrResponse->getStatusCode());

        return $response;
    }

    private function generateHttpResponseFromException(OAuthServerException $exception)
    {
        $headers = $exception->getHttpHeaders();

        $payload = $exception->getPayload();

        $response = new Response();

        if ($exception->getRedirectUri() !== null) {
            $redirectUri = $exception->getRedirectUri();
            $redirectUri .= (str_contains($redirectUri, '?') === false) ? '?' : '&';
            return new RedirectResponse($redirectUri . http_build_query($payload), 302);
        }

        foreach ($headers as $header => $content) {
            $response->headers->set($header, $content);
        }

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        $jsonEncodedPayload = json_encode($payload);

        $responseBody = $jsonEncodedPayload === false ? 'JSON encoding of payload failed' : $jsonEncodedPayload;

        $response->setContent($responseBody);
        $response->setStatusCode($exception->getHttpStatusCode());
        return $response;
    }

    private function withCors(Response $response, Request $request): Response
    {
        $referrer = $request->headers->get('Referer');
        $origin = '*';
        if ($referrer) {
            $scheme = parse_url($referrer, PHP_URL_SCHEME);
            $host = parse_url($referrer, PHP_URL_HOST);
            $port = parse_url($referrer, PHP_URL_PORT);
            $origin = $scheme . '://' . $host . ($port ? ':' . $port : '');
        }

        $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin', '*'));
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');

        return $response;
    }
}