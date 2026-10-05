<?php 

namespace App\Core\Mvc\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiController extends AbstractController
{
    public function dispatch($action, $request)
    {
        $response = parent::dispatch($action, $request);
        if (is_array($response)) {
            return new JsonResponse($response);
        }
        return $response;
    }
}