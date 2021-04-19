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
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\StopDirective;
use MaxBeckers\AmazonAlexa\Response\Response;

class PauseStopIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "AMAZON.PauseIntent",
        "AMAZON.StopIntent",
        "AMAZON.CancelIntent"
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
     */
    public function handleRequest(Request $request): Response
    {
        $stopDirective = StopDirective::create();

        $this->responseHelper->directive($stopDirective);

        return $this->responseHelper->getResponse();
    }
}
