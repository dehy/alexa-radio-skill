<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\StopDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\ClearDirective;
use MaxBeckers\AmazonAlexa\Response\Response;

class PauseStopIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "AMAZON.PauseIntent",
        "AMAZON.StopIntent"
    ];

    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest
            && in_array($request->request->intent->name, $this->handledIntentNames);
    }

    public function handleRequest(Request $request): Response
    {
        $stopDirective = StopDirective::create();

        $this->responseHelper->directive($stopDirective);

        return $this->responseHelper->getResponse();
    }
}