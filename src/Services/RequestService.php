<?php

namespace ActivityPubLite\Services;

use ActivityPubLite\Requests\ActivityPubRequest;
use ActivityPubLite\Requests\CreateNoteActivityPubRequest;
use ActivityPubLite\Util\ActivityPubConfig;

class RequestService
{

    public function __construct(
        protected ActivityPubConfig $config,
    )
    {
    }


    public function sendRequestToFollowers(CreateNoteActivityPubRequest $request): void {
        $groupedByHost = [];

        foreach ($this->config->storage->getAllFollowers() as $follower) {
            $host = $follower['host'];

            if(empty($groupedByHost[$host])) {
                $groupedByHost[$host] = [
                    'host' => $host,
                    'inbox' => $follower['sharedInbox'],
                    'followers' => []
                ];
            }

            $groupedByHost[$host]['followers'][] = $follower['actor'];
        }

        foreach($groupedByHost as $host) {
            $request->setTo($host['followers']);

            $request->build();
            $request->sendRequest($host['host'], path: $host['inbox']);
        }

    }



}