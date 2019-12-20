<?php

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
    public static function playDirectiveWithConfig(AppConfig $appConfig)
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
     * @return VideoLaunchDirective
     * @throws Exception
     */
    public static function videoLaunchDirectiveWithConfig(AppConfig $appConfig)
    {
        $stream_uri = $appConfig->getParameter("video_stream_uri");

        $title = $appConfig->getMetadata('title');
        $subtitle = $appConfig->getMetadata('subtitle');
        $metadata = VideoMetadata::create($title, $subtitle);
        $videoItem = VideoItem::create($stream_uri, $metadata);
        return VideoLaunchDirective::create($videoItem);
    }
}