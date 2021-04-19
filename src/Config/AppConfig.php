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

namespace App\Config;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use function array_key_exists;
use function array_keys;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function explode;
use function json_decode;
use function json_encode;
use function json_last_error;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_split;
use function simplexml_load_string;
use const JSON_ERROR_NONE;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;
use const PREG_SPLIT_NO_EMPTY;

class AppConfig
{
    const LOCALES = ["de-DE", "en-CA", "en-GB", "en-US", "fr-FR"];

    private $logger;

    private $parameters;
    private $hooks;
    private $metadatas;
    private $endpoints;

    // Cache for requests
    private $fetchCache = [];
    // Cache for datas
    private $cache = [];

    public function __construct(LoggerInterface $logger, string $appConfigFilepath)
    {
        $this->logger = $logger;

        try {
            $appConfig = Yaml::parseFile($appConfigFilepath);
            $this->parameters = $appConfig["parameters"];
            $this->hooks = $appConfig["hooks"];
            $this->metadatas = $appConfig["metadatas"];
            $this->endpoints = $appConfig["endpoints"];
        } catch (ParseException $exception) {
            $logger->critical(printf('Unable to parse the YAML string: %s', $exception->getMessage()));
        }
    }

    /**
     * @param string $hookName
     * @param string $locale
     * @return string
     * @throws Exception
     */
    public function getHook(string $hookName, string $locale): string
    {
        if (!array_key_exists($hookName, $this->hooks) || !in_array($locale, self::LOCALES) || !array_key_exists($locale, $this->hooks[$hookName])) {
            throw new Exception("Invalid locale '$locale' for hook '$hookName'");
        }

        $sentence = $this->hooks[$hookName][$locale];

        if (in_array($sentence, self::LOCALES)) {
            $newLocale = $sentence;
            $sentence = $this->getHook($hookName, $newLocale);
        }

        return $this->parse($sentence);
    }

    public function getMetadata(string $metadataName): ?string
    {
        if (!array_key_exists($metadataName, $this->metadatas)) {
            throw new Exception("Invalid metadata '$metadataName'");
        }

        $sentence = $this->metadatas[$metadataName];
        return $this->parse($sentence);
    }

    /**
     * @param string $parameterName
     * @return string
     * @throws Exception
     */
    public function getParameter(string $parameterName): string
    {
        if (!array_key_exists($parameterName, $this->parameters)) {
            throw new Exception("Invalid parameter '$parameterName'");
        }

        return $this->parameters[$parameterName];
    }

    /**
     * @param string $string
     * @return string
     * @throws Exception
     */
    private function parse(?string $string): ?string
    {
        $this->logger->debug("The string is '$string'");
        if ($string === null) {
            return null;
        }

        $tags = [];
        if (!preg_match_all('/%([^%]+)%/', $string, $tags, PREG_SET_ORDER)) {
            return $string;
        }

        $endpoints = [];
        foreach ($tags as $tag) {
            $tagValue = $tag[1];
            $this->logger->debug("The tag is '$tagValue'");
            if (array_key_exists($tagValue, $this->cache)) {
                $this->logger->debug("Found tag '$tagValue' in cache");
                return $this->cache[$tagValue];
            }
            list($endpoint, $path) = explode(":", $tagValue);
            if (!array_key_exists($endpoint, $this->endpoints)) {
                throw new Exception("Undefined endpoint '$endpoint'");
            }
            $endpoints[$endpoint][] = $path;
        }

        $this->logger->debug("endpoints: ".implode(", ", array_keys($endpoints)));

        foreach ($endpoints as $endpoint => $paths) {
            $this->logger->debug("$endpoint: ".implode(", ", $paths));

            $type = $this->endpoints[$endpoint]["type"];
            $source = $this->endpoints[$endpoint]["source"];
            $cacheKey = base64_encode("$source.$type");
            if (array_key_exists($cacheKey, $this->fetchCache)) {
                $data = $this->fetchCache[$cacheKey];
            } else {
                $data = $this->fetch($source, $type);
                $this->fetchCache[$cacheKey] = $data;
            }

            $this->logger->debug("data: ".print_r($data, true));

            $stringPart = "";
            foreach ($paths as $path) {
                $updatedStringPart = "";
                $this->logger->debug("reminder, the string: ".$string);
                if ($this->parseConditionPass($path)) { // Check if syntax has a "?" and if it passes
                    $pathRoad = explode(".", $path);
                    $filteredData = $data;
                    foreach ($pathRoad as $pathStep) {
                        if (!$filteredData) {
                            $filteredData = null;
                            break;
                        }
                        if (is_array($filteredData) && array_key_exists($pathStep, $filteredData)) {
                            $filteredData = $filteredData[$pathStep];
                        }
                    }
                    $tag = '%'.$endpoint.':'.$path.'%';

                    $stringPart = $this->parseExtractStringForTag($tag, $string, $path, $filteredData);
                    if ($filteredData) {
                        $updatedStringPart = preg_replace("/$tag/", $filteredData, $stringPart);
                        if (mb_substr($updatedStringPart, 0, 1) == "{") {
                            $updatedStringPart = mb_substr($updatedStringPart, 1); // Removes {
                            $updatedStringPart = mb_substr($updatedStringPart, 0, mb_strlen($updatedStringPart)-1); // Removes }
                        }
                    } else {
                        $updatedStringPart = "";
                    }
                    $this->cache[$tag] = $filteredData;
                }
                
                $this->logger->debug("updated string part: ".$updatedStringPart);

                $stringPart = preg_quote($stringPart, "/");
                $string = preg_replace("/$stringPart/", $updatedStringPart, $string);
            }

            return $string;
        }

        return "";
    }

    /**
     * @param $condition
     * @return bool
     */
    private function parseConditionPass($condition): bool
    {
        if (mb_substr($condition, 0, 1) !== "?") {
            $this->logger->debug("'$condition' is not a test => pass");
            return true;
        }
        $this->logger->debug("'$condition' is a test");
        $condition = mb_substr($condition, 1);

        $conditionParts = preg_split("/<(<=)>(>=)(==)/", $condition, null, PREG_SPLIT_NO_EMPTY);
        $this->logger->debug("condition parts: ".print_r($conditionParts, true));
        $this->logger->debug("left: ".$conditionParts[0]);
        $this->logger->debug("right: ".$conditionParts[1]);

        return false;
    }

    /**
     * @param string $tag
     * @param string $string
     * @param string $path
     * @param string|null $data
     * @return string
     */
    private function parseExtractStringForTag(string $tag, string $string, string $path, ?string $data): string
    {
        $this->logger->debug("$tag => $data");
        
        // Find $tag position
        preg_match($tag, $string, $matches, PREG_OFFSET_CAPTURE);
        if (!$matches) {
            return "";
        }
        $tagPos = $matches[0][1];

        $this->logger->debug("tag starts at pos ".$tagPos);

        $openingBracketPos = null;
        $closingBracketPos = null;

        // Find starting bracket
        $closingBrCount = 0;
        for ($pos = $tagPos; $pos >= 0; $pos--) {
            $chr = mb_substr($string, $pos, 1);
            if ($chr === "}") {
                $closingBrCount += 1;
                continue;
            }
            if ($chr === "{") {
                if ($closingBrCount > 0) {
                    $closingBrCount -= 1;
                    continue;
                }
                $openingBracketPos = $pos;
                break;
            }
        }

        $this->logger->debug("bracket opens at pos ".$openingBracketPos);

        $openingBrCount = 0;
        $maxPos = mb_strlen($string) - 1;
        //$this->logger->debug("string part max size: ".$maxPos);
        for ($pos = $openingBracketPos; $pos <= $maxPos; $pos++) {
            $chr = mb_substr($string, $pos, 1);
            //$this->logger->debug("chr=".$chr."; pos=".$pos);
            if ($chr === "{") {
                $openingBrCount += 1;
                //$this->logger->debug("openingBrCount=".$openingBrCount);
                continue;
            }
            if ($chr === "}") {
                if ($openingBrCount > 0) {
                    $openingBrCount -= 1;
                    //$this->logger->debug("openingBrCount=".$openingBrCount);
                    if ($openingBrCount === 0) {
                        $closingBracketPos = $pos;
                        break;
                    }
                    continue;
                }
                break;
            }
        }

        $this->logger->debug("bracket closes at pos ".$closingBracketPos);

        if ($openingBracketPos === null || $closingBracketPos === null) {
            return $string;
        }
        $stringPartLength = $closingBracketPos - $openingBracketPos + 1;
        $this->logger->debug("string part length: ".$stringPartLength);
        $stringPart = mb_substr($string, $openingBracketPos, $stringPartLength);
        $this->logger->debug("string part: ".$stringPart);

        return $stringPart;
    }

    /**
     * @param string $endpoint
     * @param string $type
     * @return array|null
     * @throws Exception
     */
    private function fetch(string $endpoint, string $type): ?array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($type === "json") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        } elseif ($type === "xml") {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml'));
        } else {
            throw new Exception("Unhandled endpoint type '$type'");
        }

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return null;
        }

        $data = null;
        try {
            if ($type === "json") {
                $data = json_decode($response, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    return null;
                }
            }
            if ($type === "xml") {
                $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
                if ($xml === false) {
                    return null;
                }
                $json = json_encode($xml);
                if ($json === false) {
                    return null;
                }
                $data = json_decode($json, true);
                if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                    return null;
                }
            }
        } catch (Exception $e) {
            return null;
        }

        return $data;
    }
}
