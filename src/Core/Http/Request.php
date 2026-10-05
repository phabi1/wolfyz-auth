<?php

namespace App\Core\Http;

use App\Core\Lib\Bag;

class Request
{
    public readonly Bag $query;

    public readonly Bag $body;

    public readonly Bag $headers;

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        array $query,
        array $body,
        array $headers,
    ) {
        $this->query = new Bag($query);
        $this->body = new Bag($body);
        $this->headers = new Bag($headers);
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $body = $_POST;
        if (str_contains($contentType, 'application/json')) {
            $body = json_decode(file_get_contents('php://input'), true) ?? [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', substr($key, 5));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self($method, rtrim($path, '/') ?: '/', $_GET, $body, $headers);
    }

    public function header(string $name): ?string
    {
        return $this->headers->get(strtoupper($name));
    }

    public function bearerToken(): ?string
    {
        $authorization = $this->header('Authorization');
        if ($authorization && preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
