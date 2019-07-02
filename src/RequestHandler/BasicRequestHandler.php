<?php

/*******************************************************
 * Copyright (C) 2018 Akerbis <https://www.akerbis.com>
 *
 * This file is part of Akerbis' Alexa Radio Skill.
 *
 * Akerbis' Alexa Radio Skill can not be copied, modified
 * and/or distributed without the express permission of AkerBis
 *******************************************************/

namespace App\RequestHandler;

use App\Config\AppConfig;
use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;

abstract class BasicRequestHandler extends AbstractRequestHandler
{
    protected $responseHelper;
    protected $appConfig;

    /**
     * BasicRequestHandler constructor.
     * @param ResponseHelper $responseHelper
     * @param AppConfig $appConfig
     * @param null $amazonAppId
     */
    public function __construct(ResponseHelper $responseHelper, AppConfig $appConfig, $amazonAppId = null)
    {
        $this->responseHelper = $responseHelper;
        $this->appConfig = $appConfig;

        $this->supportedApplicationIds = [$amazonAppId];
    }
}
