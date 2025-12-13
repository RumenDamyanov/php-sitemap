<?php

/**
 * Symfony component stubs for testing without Symfony installation.
 *
 * These stubs allow testing Symfony adapters without requiring
 * the full Symfony framework as a dependency.
 */

namespace Symfony\Component\HttpFoundation
{
    if (!class_exists(Response::class)) {
        class Response
        {
            public $headers;
            protected $content;
            protected $statusCode;

            public function __construct($content = '', $status = 200, array $headers = [])
            {
                $this->content = $content;
                $this->statusCode = $status;
                $this->headers = new class ($headers) {
                    private $headers;

                    public function __construct(array $headers)
                    {
                        $this->headers = $headers;
                    }

                    public function get($key, $default = null)
                    {
                        return $this->headers[$key] ?? $default;
                    }

                    public function set($key, $value)
                    {
                        $this->headers[$key] = $value;
                    }
                };

                // Set headers
                foreach ($headers as $key => $value) {
                    $this->headers->set($key, $value);
                }
            }

            public function getContent()
            {
                return $this->content;
            }

            public function getStatusCode()
            {
                return $this->statusCode;
            }
        }
    }
}
