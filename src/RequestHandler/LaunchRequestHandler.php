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
use MaxBeckers\AmazonAlexa\Request\Request\Standard\LaunchRequest;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\AudioItem;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Metadata as AudioMetadata;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\PlayDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Stream;
use MaxBeckers\AmazonAlexa\Response\Directives\Display\Image;
use MaxBeckers\AmazonAlexa\Response\Directives\VideoApp\Metadata as VideoMetadata;
use MaxBeckers\AmazonAlexa\Response\Directives\VideoApp\VideoItem;
use MaxBeckers\AmazonAlexa\Response\Directives\VideoApp\VideoLaunchDirective;
use MaxBeckers\AmazonAlexa\Response\Response;

class LaunchRequestHandler extends BasicRequestHandler
{
    /**
     * @param Request $request
     * @return bool
     */
    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof LaunchRequest;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function handleRequest(Request $request): Response
    {
        $supportedInterfaces = array_keys($request->context->system->device->supportedInterfaces);
        $locale = $request->request->locale;
        if (in_array("VideoApp", $supportedInterfaces)) {
            $this->handleVideoRequest($locale);
            $this->responseHelper->responseBody->shouldEndSession = null;
        } elseif (in_array("AudioPlayer", $supportedInterfaces)) {
            $this->handleAudioRequest($locale);
        }

        return $this->responseHelper->getResponse();
    }

    /**
     * @param string $locale
     * @throws Exception
     */
    protected function handleAudioRequest($locale)
    {
        $stream_uri = $this->appConfig->getParameter("audio_stream_uri");

        $title = $this->appConfig->getMetadata('title');
        $subtitle = $this->appConfig->getMetadata('subtitle');
        $art = null;
        $backgroundImage = null;

        $artUrl = $this->appConfig->getMetadata('art');
        if ($artUrl) {
            $art = Image::create($title, [["url" => $artUrl]]);
        }
        $backgroundImageUrl = $this->appConfig->getMetadata('backgroundImage');
        if ($backgroundImageUrl) {
            $backgroundImage = Image::create(null, [["url" => $backgroundImageUrl]]);
        }
        $metadata = AudioMetadata::create($title, $subtitle, $art, $backgroundImage);

        $stream = Stream::create($stream_uri, md5($stream_uri));
        $audioItem = AudioItem::create($stream, $metadata);
        $playDirective = PlayDirective::create($audioItem);

        $introText = $this->appConfig->getHook("beforePlayAudio", $locale);
        if ($introText) {
            $introText = "<speak>".$introText."</speak>";
            $this->responseHelper->respondSsml($introText, true);
        }
        $this->responseHelper->directive($playDirective);
    }

    /**
     * @param string $locale
     * @throws Exception
     */
    protected function handleVideoRequest($locale)
    {
        $stream_uri = $this->appConfig->getParameter("video_stream_uri");

        $title = $this->appConfig->getMetadata('title');
        $subtitle = $this->appConfig->getMetadata('subtitle');
        $metadata = VideoMetadata::create($title, $subtitle);
        $videoItem = VideoItem::create($stream_uri, $metadata);
        $playDirective = VideoLaunchDirective::create($videoItem);

        $introText = $this->appConfig->getHook("beforePlayVideo", $locale);
        if ($introText) {
            $introText = "<speak>".$introText."</speak>";
            $this->responseHelper->respondSsml($introText, true);
        }
        $this->responseHelper->directive($playDirective);
    }
}
