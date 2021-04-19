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
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
use MaxBeckers\AmazonAlexa\Response\Response;

class LaunchRequestHandler extends BasicRequestHandler
{
    /**
     * @param Request $request
     * @return bool
     */
    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof LaunchRequest;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function handleRequest(Request $request): Response
    {
        $supportedInterfaces = array_keys($request->context->system->device->supportedInterfaces);
        $locale = $request->request->locale;
        if (in_array("VideoApp", $supportedInterfaces) && true === DirectiveHelper::videoStreamIsAvailable($this->appConfig)) {
            $this->handleVideoRequest($locale);
            $this->responseHelper->responseBody->shouldEndSession = null;
        } elseif (in_array("AudioPlayer", $supportedInterfaces)) {
            $this->handleAudioRequest($locale);
        }

        return $this->responseHelper->getResponse();
    }

    /**
     * @param string $locale
     * @throws Exception
     */
    protected function handleAudioRequest($locale)
    {
        $playDirective = DirectiveHelper::playDirectiveWithConfig($this->appConfig);

        $introText = $this->appConfig->getHook("beforePlayAudio", $locale);
        if ($introText) {
            $introText = "<speak>".$introText."</speak>";
            $this->responseHelper->respondSsml($introText, true);
        }
        $this->responseHelper->directive($playDirective);
    }

    /**
     * @param string $locale
     * @throws Exception
     */
    protected function handleVideoRequest($locale)
    {
        $videoLaunchDirective = DirectiveHelper::videoLaunchDirectiveWithConfig($this->appConfig);

        $introText = $this->appConfig->getHook("beforePlayVideo", $locale);
        if ($introText) {
            $introText = "<speak>".$introText."</speak>";
            $this->responseHelper->respondSsml($introText, true);
        }
        $this->responseHelper->directive($videoLaunchDirective);
    }
}
