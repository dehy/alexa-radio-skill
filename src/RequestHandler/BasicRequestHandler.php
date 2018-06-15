<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;

abstract class BasicRequestHandler extends AbstractRequestHandler
{
    protected $responseHelper;
    
    public function __construct(ResponseHelper $responseHelper, $amazonAppId = null)
    {
        $this->responseHelper = $responseHelper;

        $this->supportedApplicationIds = [$amazonAppId];
    }
}
