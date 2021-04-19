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

use App\Helper\DirectiveHelper;
use Exception;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Response\Response;

class ResumeIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "AMAZON.ResumeIntent",
        "AMAZON.StartOverIntent",
    ];

    /**
     * @param Request $request
     * @return bool
     */
    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof IntentRequest
            && in_array($request->request->intent->name, $this->handledIntentNames);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function handleRequest(Request $request): Response
    {
        $directive = null;
        $supportedInterfaces = array_keys($request->context->system->device->supportedInterfaces);
        if (in_array("VideoApp", $supportedInterfaces) && true === DirectiveHelper::videoStreamIsAvailable($this->appConfig)) {
            $directive = DirectiveHelper::videoLaunchDirectiveWithConfig($this->appConfig);
            $this->responseHelper->responseBody->shouldEndSession = null;
        } elseif (in_array("AudioPlayer", $supportedInterfaces)) {
            $directive = DirectiveHelper::playDirectiveWithConfig($this->appConfig);

        }
        $this->responseHelper->directive($directive);

        return $this->responseHelper->getResponse();
    }
}
