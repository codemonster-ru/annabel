<?php

namespace Annabel\Http;

class Response
{
    public function __construct(
        protected string $content,
        protected int $status = 200,
        protected array $headers = []
    ) {
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo $this->content;
    }
}
