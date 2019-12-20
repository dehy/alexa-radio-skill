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
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Response\Response;

class OnAirIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "OnAirIntent"
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
        $supportedInterfaces = array_keys($request->context->system->device->supportedInterfaces);
        $locale = $request->request->locale;
        $onAirText = "";
        if (in_array("VideoApp", $supportedInterfaces) && true === DirectiveHelper::videoStreamIsAvailable($this->appConfig)) {
            $onAirText = "<speak>".$this->appConfig->getHook("onAirVideo", $locale)."</speak>";
            $this->responseHelper->responseBody->shouldEndSession = null;
        } elseif (in_array("AudioPlayer", $supportedInterfaces)) {
            $onAirText = "<speak>".$this->appConfig->getHook("onAirAudio", $locale)."</speak>";
        }
        $this->responseHelper->respondSsml($onAirText, true);

        return $this->responseHelper->getResponse();
    }
}
