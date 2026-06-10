<?php

namespace Codemonster\HttpClient\Contracts;

use Codemonster\HttpClient\HttpResponse;
use Codemonster\HttpClient\RequestData;

interface TransportInterface
{
    public function send(RequestData $request): HttpResponse;
}
