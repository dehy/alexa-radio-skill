<?php

/*
 * Alexa Radio Skill - An Alexa Skill for your own webradio
 * Copyright (C) 2021 Arnaud de Mouhy
 *
 * This file is part of Alexa Radio Skill.
 *
 * Alexa Radio Skill is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Alexa Radio Skill is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Alexa Radio Skill.  If not, see <http://www.gnu.org/licenses/>.
 */

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
