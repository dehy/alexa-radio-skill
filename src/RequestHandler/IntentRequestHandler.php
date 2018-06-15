<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\StopDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\ClearDirective;
use MaxBeckers\AmazonAlexa\Response\Response;

class IntentRequestHandler extends AbstractRequestHandler
{
    protected $responseHelper;
    
    public function __construct(ResponseHelper $responseHelper)
    {
        $this->responseHelper = $responseHelper;

        $this->supportedApplicationIds = ['amzn1.ask.skill.b702f9fc-fe01-4fcb-bdbd-fbd85629b491'];
    }

    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
    }

    public function handleRequest(Request $request): Response
    {
        if ($request->request->intent->name == "AMAZON.PauseIntent" || $request->request->intent->name == "AMAZON.StopIntent") {
            $stopDirective = StopDirective::create();
            //$clearDirective = ClearDirective::create();

            $this->responseHelper->directive($stopDirective);
            //$this->responseHelper->directive($clearDirective);
        }

        return $this->responseHelper->getResponse();
    }
}
