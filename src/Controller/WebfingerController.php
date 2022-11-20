<?php

namespace ActivityPubLite\Controller;

class WebfingerController extends Controller
{

    public function webfinger($resource): array
    {
        $resourceHandle = sprintf('acct:%s', $this->config->fullHandle());

        return [
            'subject' => $resourceHandle,
            'aliases' => [
                sprintf('https://%s/_aplite/user/%s', $this->config->host, $this->config->handle)
            ],
            'links' => [
                [
                  'rel' => 'http://webfinger.net/rel/profile-page',
                  'type' => 'text/html',
                  'href' => 'https://knuspermagier.de' // @TODO
                ],
                [
                    'rel' => 'self',
                    'type' => 'application/activity+json',
                    'href' => sprintf('https://%s/_aplite/user/%s', $this->config->host, $this->config->handle)
                ]
            ]
        ];
    }

}
