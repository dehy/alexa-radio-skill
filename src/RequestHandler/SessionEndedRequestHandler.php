<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\AudioItem;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\PlayDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Stream;
use MaxBeckers\AmazonAlexa\Response\Response;

class SessionEndedRequestHandler extends AbstractRequestHandler
{
    protected $responseHelper;
    
    public function __construct(ResponseHelper $responseHelper)
    {
        $this->responseHelper = $responseHelper;

        $this->supportedApplicationIds = ['amzn1.ask.skill.b702f9fc-fe01-4fcb-bdbd-fbd85629b491'];
    }

    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\SessionEndedRequest;
    }

    public function handleRequest(Request $request): Response
    {
        return $this->responseHelper->getResponse();
    }
}
