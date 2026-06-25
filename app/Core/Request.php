<?php
namespace App\Core;

class Request {
    public function getMethod(): string {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function getPath(): string {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        if ($position !== false) {
            $path = substr($path, 0, $position);
        }
        return '/' . trim($path, '/');
    }

    public function isGet(): bool {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }

    public function isAjax(): bool {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
               (isset($_SERVER['HTTP_ACCEPT']) && str_contains(strtolower($_SERVER['HTTP_ACCEPT']), 'application/json'));
    }

    public function getBody(): array {
        $body = [];
        if ($this->getMethod() === 'GET') {
            foreach ($_GET as $key => $value) {
                if (is_array($value)) {
                    $body[$key] = filter_var_array($value, FILTER_DEFAULT);
                } else {
                    $body[$key] = filter_input(INPUT_GET, $key, FILTER_DEFAULT);
                }
            }
        } else {
            // Check if JSON body
            $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
            if (str_contains(strtolower($contentType), 'application/json')) {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                if (is_array($data)) {
                    $body = $data;
                }
            } else {
                foreach ($_POST as $key => $value) {
                    if (is_array($value)) {
                        $body[$key] = filter_var_array($value, FILTER_DEFAULT);
                    } else {
                        $body[$key] = filter_input(INPUT_POST, $key, FILTER_DEFAULT);
                    }
                }
            }
        }
        return $body;
    }

    public function get(string $key, mixed $default = null): mixed {
        $body = $this->getBody();
        return $body[$key] ?? $default;
    }

    public function getFiles(): array {
        return $_FILES;
    }

    public function getIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
