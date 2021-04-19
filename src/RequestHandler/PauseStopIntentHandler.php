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

namespace App\RequestHandler;

use MaxBeckers\AmazonAlexa\Request\Request;
use MaxBeckers\AmazonAlexa\Request\Request\Standard\IntentRequest;
use MaxBeckers\AmazonAlexa\Response\Directives\AudioPlayer\StopDirective;
use MaxBeckers\AmazonAlexa\Response\Response;

class PauseStopIntentHandler extends BasicRequestHandler
{
    protected $handledIntentNames = [
        "AMAZON.PauseIntent",
        "AMAZON.StopIntent",
        "AMAZON.CancelIntent"
    ];

    /**
     * @param Request $request
     * @return bool
     */
    public function supportsRequest(Request $request): bool
    {
        return $request->request instanceof IntentRequest
            && in_array($request->request->intent->name, $this->handledIntentNames);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Request $request): Response
    {
        $stopDirective = StopDirective::create();

        $this->responseHelper->directive($stopDirective);

        return $this->responseHelper->getResponse();
    }
}
