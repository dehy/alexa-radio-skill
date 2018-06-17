<?php

namespace App\RequestHandler;

use App\Config\AppConfig;
use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;

abstract class BasicRequestHandler extends AbstractRequestHandler
{
    protected $responseHelper;
    protected $appConfig;
    
    public function __construct(ResponseHelper $responseHelper, AppConfig $appConfig, $amazonAppId = null)
    {
        $this->responseHelper = $responseHelper;
        $this->appConfig = $appConfig;

        $this->supportedApplicationIds = [$amazonAppId];
    }
}
