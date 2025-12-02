<?php
namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '';

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function json(array $data, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setBody(json_encode($data));
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        $this->send();
        exit();
    }

    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send body
        echo $this->body;
    }

    public function html(string $html, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->setBody($html);
        return $this;
    }

    public function notFound(string $message = 'Not Found'): void
    {
        $this->json(['error' => $message], 404)->send();
        exit();
    }

    public function error(string $message = 'Internal Server Error', int $statusCode = 500): void
    {
        $this->json(['error' => $message], $statusCode)->send();
        exit();
    }
}
