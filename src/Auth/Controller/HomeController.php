<?php

namespace App\Auth\Controller;

use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Mvc\Controller\AbstractController;

class HomeController extends AbstractController
{
    public function indexAction(Request $request): Response
    {
        if (!in_array($request->method, ['GET', 'HEAD'], true)) {
            return new Response('', 405, ['Allow' => 'GET, HEAD']);
        }

        $response = $this->render('auth/home', [
            'isLoggedIn' => $this->getService('auth.authentication')->isLoggedIn(),
        ]);
        $response->headers->set('Cache-Control', 'no-store');

        if ($request->method === 'HEAD') {
            return new Response('', 200, $response->headers->all());
        }

        return $response;
    }
}
