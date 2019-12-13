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

use Exception;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Response\Response;

class UnavailableIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "AMAZON.NextIntent",
        "AMAZON.PreviousIntent",
        "AMAZON.RepeatIntent",
        "AMAZON.LoopOffIntent",
        "AMAZON.ShuffleOffIntent",
        "AMAZON.HelpIntent",
        "AMAZON.NavigateHomeIntent",
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
        $locale = $request->request->locale;
        $unavailableText = $this->appConfig->getHook("nextPreviousRepeatWarning", $locale);
        if ($unavailableText) {
            $unavailableText = "<speak>".$unavailableText."</speak>";
            $this->responseHelper->respondSsml($unavailableText, true);
        }

        return $this->responseHelper->getResponse();
    }
}
