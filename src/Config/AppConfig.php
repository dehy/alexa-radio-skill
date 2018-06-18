<?php

/*******************************************************
 * Copyright (C) 2018 Akerbis <https://www.akerbis.com>
 *
 * This file is part of Akerbis' Alexa Radio Skill.
 *
 * Akerbis' Alexa Radio Skill can not be copied, modified
 * and/or distributed without the express permission of AkerBis
 *******************************************************/

namespace App\Config;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class AppConfig
{
    const LOCALES = ["de-DE", "en-CA", "en-GB", "en-US", "fr-FR"];

    private $logger;

    private $parameters;
    private $hooks;
    private $endpoints;

    public function __construct(LoggerInterface $logger, string $appConfigFilepath)
    {
        $this->logger = $logger;

        try {
            $appConfig = Yaml::parseFile($appConfigFilepath);
            $this->parameters = $appConfig["parameters"];
            $this->hooks = $appConfig["hooks"];
            $this->endpoints = $appConfig["endpoints"];
        } catch (ParseException $exception) {
            printf('Unable to parse the YAML string: %s', $exception->getMessage());
        }
    }

    public function getHook(string $hookName, string $locale): string
    {
        if (!array_key_exists($hookName, $this->hooks) || !in_array($locale, self::LOCALES) || !array_key_exists($locale, $this->hooks[$hookName])) {
            throw new \Exception("Invalid locale '$locale' for hook '$hookName'");
        }

        $sentence = $this->hooks[$hookName][$locale];

        if (in_array($sentence, self::LOCALES)) {
            $newLocale = $sentence;
            $sentence = $this->getHook($hookName, $newLocale);
        }

        return $this->parse($sentence);
    }

    public function getParameter(string $parameterName): string
    {
        if (!array_key_exists($parameterName, $this->parameters)) {
            throw new \Exception("Invalid parameter '$parameterName'");
        }

        return $this->parameters[$parameterName];
    }

    private function parse(string $string): string
    {
        $this->logger->debug("The string is '$string'");

        $tags = [];
        if (!\preg_match_all("/%([^ %]+)%/", $string, $tags, \PREG_SET_ORDER)) {
            return $string;
        }

        $endpoints = [];
        foreach ($tags as $tag) {
            list($endpoint, $path) = \explode(":", $tag[1]);
            if (!\array_key_exists($endpoint, $this->endpoints)) {
                throw new \Exception("Undefined endpoint '$endpoint'");
            }
            $endpoints[$endpoint][] = $path;
        }

        $this->logger->debug("endpoints: ".implode(", ", \array_keys($endpoints)));

        foreach ($endpoints as $endpoint => $paths) {
            $this->logger->debug("$endpoint: ".implode(", ", $paths));

            $type = $this->endpoints[$endpoint]["type"];
            $source = $this->endpoints[$endpoint]["source"];
            $data = $this->fetch($source, $type);

            $this->logger->debug("data: ".print_r($data, true));

            foreach ($paths as $path) {
                $updatedStringPart = "";
                $this->logger->debug("reminder, the string: ".$string);
                if ($this->parseConditionPass($path)) { // Check if syntax has a "?" and if it passes
                    $pathRoad = \explode(".", $path);
                    $filteredData = $data;
                    foreach ($pathRoad as $pathStep) {
                        if (!$filteredData) {
                            $filteredData = null;
                            break;
                        }
                        if (is_array($filteredData) && \array_key_exists($pathStep, $filteredData)) {
                            $filteredData = $filteredData[$pathStep];
                        }
                    }
                    $tag = '%'.$endpoint.':'.$path.'%';

                    $stringPart = $this->parseExtractStringForTag($tag, $string, $path, $filteredData);
                    if ($filteredData) {
                        $updatedStringPart = \preg_replace("/$tag/", $filteredData, $stringPart);
                        $updatedStringPart = \mb_substr($updatedStringPart, 1); // Removes {
                        $updatedStringPart = \mb_substr($updatedStringPart, 0, \mb_strlen($updatedStringPart)-1); // Removes }
                    } else {
                        $updatedStringPart = "";
                    }
                }
                
                $this->logger->debug("updated string part: ".$updatedStringPart);

                $string = \preg_replace("/$stringPart/", $updatedStringPart, $string);
            }

            return $string;
        }
    }

    private function parseConditionPass($condition): bool
    {
        if (\mb_substr($condition, 0, 1) !== "?") {
            $this->logger->debug("'$condition' is not a test => pass");
            return true;
        }
        $this->logger->debug("'$condition' is a test");
        $condition = \mb_substr($condition, 1);

        $conditionParts = \preg_split("/<(<=)>(>=)(==)/", $condition, null, \PREG_SPLIT_NO_EMPTY);
        $this->logger->debug("condition parts: ".print_r($conditionParts, true));
        $this->logger->debug("left: ".$conditionParts[0]);
        $this->logger->debug("right: ".$conditionParts[1]);

        return false;
    }

    private function parseExtractStringForTag(string $tag, string $string, string $path, ?string $data): string
    {
        $this->logger->debug("$tag => $data");
        
        // Find $tag position
        \preg_match($tag, $string, $matches, \PREG_OFFSET_CAPTURE);
        if (!$matches) {
            return "";
        }
        $tagPos = $matches[0][1];

        $this->logger->debug("tag starts at pos ".$tagPos);

        $openingBracketPos = null;
        $closingBracketPos = null;

        // Find starting bracket
        $closingBrCount = 0;
        $pos = $tagPos;
        for ($pos; $pos >= 0; $pos--) {
            $chr = \mb_substr($string, $pos, 1);
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
        $pos = $openingBracketPos;
        $maxPos = \mb_strlen($string) - 1;
        //$this->logger->debug("string part max size: ".$maxPos);
        for ($pos; $pos <= $maxPos; $pos++) {
            $chr = \mb_substr($string, $pos, 1);
            $this->logger->debug("chr=".$chr."; pos=".$pos);
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

        $stringPartLength = $closingBracketPos - $openingBracketPos + 1;
        $this->logger->debug("string part length: ".$stringPartLength);
        $stringPart = \mb_substr($string, $openingBracketPos, $stringPartLength);
        $this->logger->debug("string part: ".$stringPart);

        return $stringPart;
    }

    private function fetch(string $endpoint, string $type): array
    {
        $ch = \curl_init();
        \curl_setopt($ch, CURLOPT_URL, $endpoint);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($type === "json") {
            \curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        } elseif ($type === "xml") {
            \curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml'));
        } else {
            throw new \Exception("Unhandled endpoint type '$type'");
        }

        $response = \curl_exec($ch);
        \curl_close($ch);

        if ($response === false) {
            return "";
        }

        try {
            if ($type === "json") {
                $data = \json_decode($response, true);
            }
            if ($type === "xml") {
                $xml = \simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
                $json = \json_encode($xml);
                $data = \json_decode($json, true);
            }
        } catch (\Exception $e) {
            return $string;
        }

        return $data;
    }
}
