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
        $supportedInterfaces = array_keys($request->context->system->device->supportedInterfaces);
        if (in_array("VideoApp", $supportedInterfaces)) {
            $directive = DirectiveHelper::videoLaunchDirectiveWithConfig($this->appConfig);
            $this->responseHelper->responseBody->shouldEndSession = null;
        } elseif (in_array("AudioPlayer", $supportedInterfaces)) {
            $directive = DirectiveHelper::playDirectiveWithConfig($this->appConfig);

        }
        $this->responseHelper->directive($directive);

        return $this->responseHelper->getResponse();
    }
}
