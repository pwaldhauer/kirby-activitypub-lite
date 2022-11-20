<?php

namespace ActivityPubLite\Util;

use ActivityPubLite\Storage\Storage;

class ActivityPubConfig
{

    public function __construct(
        public string $host,
        public string $handle,
        public string $displayName,
        public Storage $storage,
        public ?array $profileOptions,
        public ?array $keyPair
    )
    {
    }

    public function actorString(): string {
        return sprintf('https://%s/_aplite/user/%s', $this->host, $this->handle);
    }

    public function fullHandle(): string
    {
        return sprintf('%s@%s', $this->handle, $this->host);
    }

    public function keyName(): string
    {
        return sprintf('https://%s/_aplite/user/%s#main', $this->host, $this->handle);
    }

    public function publicKeyString(): string
    {
        return file_get_contents($this->keyPair['publicKey']);
    }

    public function privateKeyString(): string
    {
        return file_get_contents($this->keyPair['privateKey']);
    }
}