<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\RequestHandler\AbstractRequestHandler;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\AudioItem;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\PlayDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Stream;
use MaxBeckers\AmazonAlexa\Response\Response;

class LaunchRequestHandler extends AbstractRequestHandler
{
    protected $responseHelper;
    
    public function __construct(ResponseHelper $responseHelper)
    {
        $this->responseHelper = $responseHelper;

        $this->supportedApplicationIds = ['amzn1.ask.skill.b702f9fc-fe01-4fcb-bdbd-fbd85629b491'];
    }

    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
    }

    public function handleRequest(Request $request): Response
    {
        $stream = Stream::create("https://www.morow.com/morow.m3u", "morow.m3u");
        $audioItem = AudioItem::create($stream);
        $playDirective = PlayDirective::create($audioItem);

        return $this->responseHelper->directive($playDirective);
    }
}
