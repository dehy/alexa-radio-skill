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
        if (in_array("VideoApp", $supportedInterfaces)) {
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
