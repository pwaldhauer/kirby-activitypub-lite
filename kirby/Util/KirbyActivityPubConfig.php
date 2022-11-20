<?php

namespace ActivityPubLiteKirby\Util;

use ActivityPubLite\Util\ActivityPubConfig;

class KirbyActivityPubConfig extends ActivityPubConfig
{

    public function __construct()
    {
        $storageClass = \option('activitypub-lite.storage');

        $this->host = \option('activitypub-lite.host');
        $this->handle = \option('activitypub-lite.handle');
        $this->displayName = \option('activitypub-lite.displayName');
        $this->storage = new $storageClass();
        $this->profileOptions = \option('activitypub-lite.profileOptions');
        $this->keyPair = \option('activitypub-lite.keypair');
    }
}