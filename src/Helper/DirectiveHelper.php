<?php

/*
 * Alexa Radio Skill - An Alexa Skill for your own webradio
 * Copyright (C) 2021 Arnaud de Mouhy
 *
 * This file is part of Alexa Radio Skill.
 *
 * Alexa Radio Skill is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Alexa Radio Skill is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Alexa Radio Skill.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Helper;

use App\Config\AppConfig;
use Exception;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\AudioItem;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Metadata as AudioMetadata;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\PlayDirective;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\Stream;
use MaxBeckers\AmazonAlexa\Response\Directives\Display\Image;
use MaxBeckers\AmazonAlexa\Response\Directives\VideoApp\Metadata as VideoMetadata;
use MaxBeckers\AmazonAlexa\Response\Directives\VideoApp\VideoItem;
use MaxBeckers\AmazonAlexa\Response\Directives\VideoApp\VideoLaunchDirective;

/**
 * 
 */
class DirectiveHelper
{
    /**
     *
     * @param AppConfig $appConfig
     * @return PlayDirective
     * @throws Exception
     */
    public static function playDirectiveWithConfig(AppConfig $appConfig): PlayDirective
    {
        $stream_uri = $appConfig->getParameter("audio_stream_uri");

        $title = $appConfig->getMetadata('title');
        $subtitle = $appConfig->getMetadata('subtitle');
        $art = null;
        $backgroundImage = null;

        $artUrl = $appConfig->getMetadata('art');
        if ($artUrl) {
            $art = Image::create($title, [["url" => $artUrl]]);
        }
        $backgroundImageUrl = $appConfig->getMetadata('backgroundImage');
        if ($backgroundImageUrl) {
            $backgroundImage = Image::create(null, [["url" => $backgroundImageUrl]]);
        }
        $metadata = AudioMetadata::create($title, $subtitle, $art, $backgroundImage);

        $stream = Stream::create($stream_uri, md5($title.$subtitle));
        $audioItem = AudioItem::create($stream, $metadata);
        return PlayDirective::create($audioItem);
    }

    /**
     * @param AppConfig $appConfig
     * @return bool
     */
    public static function videoStreamIsAvailable(AppConfig $appConfig): bool
    {
        try {
            $stream_uri = $appConfig->getParameter("video_stream_uri");
        } catch (Exception $e) {
            return false;
        }

        if (!$stream_uri) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $stream_uri);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_TIMEOUT,4);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

        curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($http_status >= 200 && $http_status < 400) ? true : false;
    }

    /**
     * @param AppConfig $appConfig
     * @return VideoLaunchDirective
     * @throws Exception
     */
    public static function videoLaunchDirectiveWithConfig(AppConfig $appConfig): VideoLaunchDirective
    {
        $stream_uri = $appConfig->getParameter("video_stream_uri");

        $title = $appConfig->getMetadata('title');
        $subtitle = $appConfig->getMetadata('subtitle');
        $metadata = VideoMetadata::create($title, $subtitle);
        $videoItem = VideoItem::create($stream_uri, $metadata);
        return VideoLaunchDirective::create($videoItem);
    }
}