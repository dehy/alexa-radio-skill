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

use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\StopDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\ClearDirective;
use MaxBeckers\AmazonAlexa\Response\Response;

class OnAirIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "OnAirIntent"
    ];

    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest
            && in_array($request->request->intent->name, $this->handledIntentNames);
    }

    public function handleRequest(Request $request): Response
    {
        $onAirText = "<speak>".$this->appConfig->getHook("onAir", $request->request->locale)."</speak>";
        $this->responseHelper->respondSsml($onAirText, true);

        return $this->responseHelper->getResponse();
    }
}
