<?php

namespace App\Config;

use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Yaml;

class AppConfig
{
    const LOCALES = ["fr-FR", "en-US", "en-GB", "en-CA"];

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
                $this->logger->debug("the string: ".$string);
                $pathRoad = \explode(".", $path);
                $foundData = $data;
                foreach ($pathRoad as $pathStep) {
                    if (!$foundData || \is_string($foundData)) {
                        $foundData = null;
                        break;
                    }
                    $foundData = $foundData[$pathStep];
                }
                $tag = '%'.$endpoint.':'.$path.'%';

                $this->logger->debug("$tag => $foundData");
                
                // Find $tag position
                \preg_match($tag, $string, $matches, \PREG_OFFSET_CAPTURE);
                if (!$matches) {
                    continue;
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
                $this->logger->debug("string part max size: ".$maxPos);
                for ($pos; $pos <= $maxPos; $pos++) {
                    $chr = \mb_substr($string, $pos, 1);
                    $this->logger->debug("chr=".$chr."; pos=".$pos);
                    if ($chr === "{") {
                        $openingBrCount += 1;
                        $this->logger->debug("openingBrCount=".$openingBrCount);
                        continue;
                    }
                    if ($chr === "}") {
                        if ($openingBrCount > 0) {
                            $openingBrCount -= 1;
                            $this->logger->debug("openingBrCount=".$openingBrCount);
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

                $updatedStringPart = $stringPart;
                if ($foundData) {
                    $updatedStringPart = \preg_replace("/$tag/", $foundData, $stringPart);
                    $updatedStringPart = \mb_substr($updatedStringPart, 1); // Removes {
                    $updatedStringPart = \mb_substr($updatedStringPart, 0, \mb_strlen($updatedStringPart)-1); // Removes }
                } else {
                    $updatedStringPart = "";
                }
                $this->logger->debug("updated string part: ".$updatedStringPart);

                $string = \preg_replace("/$stringPart/", $updatedStringPart, $string);
            }

            return $string;
        }
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
