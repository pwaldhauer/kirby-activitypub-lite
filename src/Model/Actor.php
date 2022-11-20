<?php

namespace ActivityPubLite\Model;

class Actor
{

    public string $actor;
    public string $handle;
    public string $host;
    public string $publicKey;
    public string $inbox;
    public string $sharedInbox = '/inbox';

    public function toArray(): array
    {
        return [
            'actor' => $this->actor,
            'handle' => $this->handle,
            'host' => $this->host,
            'publicKey' => $this->publicKey,
            'inbox' => $this->inbox,
            'sharedInbox' => $this->sharedInbox,
        ];
    }

    public static function fromActorString(string $actor): ?Actor
    {
        $obj = new Actor();

        $obj->actor = $actor;

        $profile = $obj->webfinger();
        if (empty($profile)) {
            return null;
        }

        $obj->fillFromProfile($profile);
        return $obj;
    }

    private function fillFromProfile($url): void
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Accept: application/json\r\n"
            ]
        ];

        $context = stream_context_create($opts);
        $content = file_get_contents($url, false, $context);
        if (empty($content)) {
            return;
        }

        $userInfo = json_decode($content, true);
        if (empty($userInfo)) {
            return;
        }

        $this->publicKey = $userInfo['publicKey']['publicKeyPem'];

        $url = parse_url($userInfo['inbox']);

        $this->inbox = $url['path'];
    }

    private function webfinger(): ?string
    {
        $fingerResponse = file_get_contents($this->webfingerUrl());

        if (empty($fingerResponse)) {
            return null;
        }

        $json = json_decode($fingerResponse, true);

        if (empty($json)) {
            return null;
        }


        $subject = str_replace('acct:', '', $json['subject']);
        [$handle, $host] = explode('@', $subject);

        $this->handle = $handle;
        $this->host = $host;

        foreach ($json['links'] as $link) {
            if ($link['rel'] === 'self') {
                return $link['href'];
            }
        }
    }

    private function webfingerUrl(): string
    {
        if (str_contains($this->actor, '@')) {
            [, $host] = explode('@', $this->actor);
            return sprintf('https://%s/.well-known/webfinger?resource=acct:%s', $host, $this->actor);
        }

        $url = parse_url($this->actor);
        $host = $url['host'];
        $handle = substr($url['path'], strrpos($url['path'], '/') + 1);

        return sprintf('https://%s/.well-known/webfinger?resource=acct:%s@%s', $host, $handle, $host);
    }
}