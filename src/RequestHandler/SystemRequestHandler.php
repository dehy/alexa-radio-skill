<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Response\Response;

class SystemRequestHandler extends BasicRequestHandler
{
    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\System\ExceptionEncounteredRequest;
    }

    public function handleRequest(Request $request): Response
    {
        return $this->responseHelper->getResponse();
    }
}
