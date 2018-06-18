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

use App\Config\AppConfig;
use MaxBeckers\AmazonAlexa\Helper\ResponseHelper;
use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\AudioItem;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\PlayDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Stream;
use MaxBeckers\AmazonAlexa\Response\Response;

class LaunchRequestHandler extends BasicRequestHandler
{
    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof \MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
    }

    public function handleRequest(Request $request): Response
    {
        $stream_uri = $this->appConfig->getParameter("stream_uri");

        $stream = Stream::create($stream_uri, md5($stream_uri));
        $audioItem = AudioItem::create($stream);
        $playDirective = PlayDirective::create($audioItem);

        $introText = $this->appConfig->getHook("beforePlay", $request->request->locale);
        if ($introText) {
            $introText = "<speak>".$introText."</speak>";
            $this->responseHelper->respondSsml($introText, true);
        }
        $this->responseHelper->directive($playDirective);

        return $this->responseHelper->getResponse();
    }
}
