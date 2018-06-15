<?php

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\AudioItem;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\PlayDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Stream;
use MaxBeckers\AmazonAlexa\Response\Response;

class LaunchRequestHandler extends BasicRequestHandler
{
    protected $radioStreamUri;

    public function __construct(ResponseHelper $responseHelper, $amazonAppId = null, $radioStreamUri = null)
    {
        parent::__construct($responseHelper, $amazonAppId);

        $this->radioStreamUri = $radioStreamUri;
    }

    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
    }

    public function handleRequest(Request $request): Response
    {
        $stream = Stream::create($this->radioStreamUri, md5($this->radioStreamUri));
        $audioItem = AudioItem::create($stream);
        $playDirective = PlayDirective::create($audioItem);

        return $this->responseHelper->directive($playDirective);
    }
}
