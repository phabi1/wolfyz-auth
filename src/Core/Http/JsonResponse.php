<?php
namespace App\Core\Http;

class JsonResponse extends Response
{
    public function __construct(array $data, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        $headers['Cache-Control'] = 'no-store';
        $headers['Pragma'] = 'no-cache';
        parent::__construct(json_encode($data, JSON_UNESCAPED_SLASHES), $status, $headers);
    }
}