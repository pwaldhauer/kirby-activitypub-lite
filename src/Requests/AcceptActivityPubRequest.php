<?php

namespace ActivityPubLite\Requests;

use ActivityPubLite\Util\ActivityPubConfig;

class AcceptActivityPubRequest extends ActivityPubRequest
{

    public function __construct(
        protected ActivityPubConfig $config,
        private array               $followActivity
    )
    {
    }

    public function build()
    {
        $this->data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => sprintf('https://%s/_aplite/user/%s/activity/%s', $this->config->host, $this->config->handle, uniqid()),
            'type' => 'Accept',
            'actor' => sprintf('https://%s/_aplite/user/%s', $this->config->host, $this->config->handle),
            'object' => $this->followActivity
        ];
    }


}