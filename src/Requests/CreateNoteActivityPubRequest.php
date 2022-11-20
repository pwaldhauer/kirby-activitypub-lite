<?php

namespace ActivityPubLite\Requests;

use ActivityPubLite\Util\ActivityPubConfig;

class CreateNoteActivityPubRequest extends ActivityPubRequest
{

    public function __construct(
        protected ActivityPubConfig $config,
        private readonly \DateTime  $published_at,
        private readonly string     $content,
        private ?array              $to,
        private readonly ?string    $id = null,
        private readonly ?string    $url = null
    )
    {
    }

    public function setTo(array $to): void
    {
        $this->to = $to;
    }

    public function build()
    {
        $this->data = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => sprintf('https://%s/_aplite/user/%s/activity/%s', $this->config->host, $this->config->handle, uniqid()),
            'type' => 'Create',
            'actor' => $this->config->actorString(),
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'cc' => $this->to,
            'object' => [
                'id' => $this->id ?? sprintf('https://%s/_aplite/user/%s/%s', $this->config->host, $this->config->handle, uniqid()),
                'type' => 'Note',
                'published' => $this->published_at->format(\DATE_RFC3339),
                'attributedTo' => $this->config->actorString(),
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'cc' => $this->to,
                'content' => $this->content,
                'url' => $this->url ?? null
            ],

        ];
    }

}