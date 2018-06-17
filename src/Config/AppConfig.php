<?php

namespace App\Config;

use Symfony\Component\Yaml\Yaml;

class AppConfig
{
    const LOCALES = ["fr-FR", "en-US", "en-GB", "en-CA"];

    private $parameters;
    private $hooks;
    private $endpoints;

    public function __construct(string $appConfigFilepath)
    {
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
        $tags = [];
        if (!\preg_match_all("/%([^ %]+)%/", $string, $tags, PREG_SET_ORDER)) {
            return $string;
        }

        $endpoints = [];
        foreach ($tags as $tag) {
            list($endpoint, $path) = explode(":", $tag[1]);
            if (!array_key_exists($endpoint, $this->endpoints)) {
                throw new \Exception("Undefined endpoint '$endpoint'");
            }
            $endpoints[$endpoint][] = $path;
        }

        foreach ($endpoints as $endpoint => $paths) {
            $type = $this->endpoints[$endpoint]["type"];
            $source = $this->endpoints[$endpoint]["source"];
            $data = $this->fetch($source, $type);

            foreach ($paths as $path) {
                $pathRoad = explode(".", $path);
                $foundData = $data;
                foreach ($pathRoad as $pathStep) {
                    $foundData = $foundData[$pathStep];
                }

                $string = \str_replace('%'.$endpoint.':'.$path.'%', $foundData, $string);
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
