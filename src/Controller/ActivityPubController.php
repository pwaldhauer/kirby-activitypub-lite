<?php

namespace ActivityPubLite\Controller;

use ActivityPubLite\Model\Actor;
use ActivityPubLite\Requests\AcceptActivityPubRequest;
use ActivityPubLite\Util\HttpSignature;

class ActivityPubController extends Controller
{

    public function inbox(array $params): array
    {
        if(!$this->verifyRequest($params)) {
            throw new \Exception('Could not verify signature');
        }


        switch ($params['type']) {
            case 'Undo':
                $actor = Actor::fromActorString($params['actor']);
                if ($params['object']['type'] == 'Follow' && $params['object']['object'] == $this->config->actorString()) {
                    $this->config->storage->removeFollower($actor->toArray());
                }

                return ['success' => true];
            case 'Follow':
                $actor = Actor::fromActorString($params['actor']);

                $request = new AcceptActivityPubRequest($this->config, $params);
                $request->build();
                $request->sendRequest($actor->host, $actor->inbox);

                $this->config->storage->saveFollower($actor->toArray());

                return ['success' => true];
            default:
                return ['error' => 'not implemented'];
        }

    }

    public function followers(): array
    {
        $followers = $this->config->storage['getAllFollowers']();

        if (empty($_GET['page'])) {
            return [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => sprintf('https://%s/_aplite/user/%s/followers', $this->config->host, $this->config->handle),
                'type' => 'OrderedCollection',
                'totalItems' => count($followers),
                'first' => sprintf('https://%s/_aplite/user/%s/followers?page=1', $this->config->host, $this->config->handle)
            ];
        }

        $page = intval($_GET['page']);

        return [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => sprintf('https://%s/_aplite/user/%s/followers?page=%s', $this->config->host, $this->config->handle, $page),
            'partOf' => sprintf('https://%s/_aplite/user/%s/followers', $this->config->host, $this->config->handle),
            'type' => 'OrderedCollectionPage',
            'totalItems' => count($followers),
            'orderedItems' => array_map(function ($f) {
                return $f['actor'];
            }, $followers)
        ];

    }

    public function user(): array
    {
        return array_merge([
            '@context' => ['https://www.w3.org/ns/activitystreams', 'https://w3id.org/security/v1'],
            'id' => sprintf('https://%s/_aplite/user/%s', $this->config->host, $this->config->handle),

            'type' => 'Person',
            'preferredUsername' => $this->config->handle,
            'name' => $this->config->displayName,
            'manuallyApprovesFollowers' => false,
            'discoverable' => true,

            'inbox' => sprintf('https://%s/_aplite/user/%s/inbox', $this->config->host, $this->config->handle),
            'outbox' => sprintf('https://%s/_aplite/user/%s/outbox', $this->config->host, $this->config->handle),
            'following' => sprintf('https://%s/_aplite/user/%s/following', $this->config->host, $this->config->handle),
            'followers' => sprintf('https://%s/_aplite/user/%s/followers', $this->config->host, $this->config->handle),

            'publicKey' => [
                'id' => $this->config->keyName(),
                'owner' => sprintf('https://%s/_aplite/user/%s', $this->config->host, $this->config->handle),
                'publicKeyPem' => $this->config->publicKeyString()
            ]
        ], $this->config->profileOptions);
    }


    private function verifyRequest(array $params): bool
    {
        if(empty($_SERVER['HTTP_SIGNATURE']) || empty($_SERVER['HTTP_DATE'])) {
            throw new \Exception('Missing Signature and Date Headers');
        }

        $headers = [
            'host' => $_SERVER['HTTP_HOST'],
            'signature' => $_SERVER['HTTP_SIGNATURE'],
            'digest' => $_SERVER['HTTP_DIGEST'],
            'date' => $_SERVER['HTTP_DATE'],
            'content-type' => $_SERVER['CONTENT_TYPE']
        ];

        //@ Todo check time difference
        //@ Todo calculate Digest

        $parts = [];
        $tmp = explode(',', $headers['signature']);
        foreach($tmp as $part) {
            [$name, $value] = explode('=', $part, 2);
            $parts[$name] = substr($value, 1, -1);
        }

        // Build plaintext
        $plaintext = [];
        foreach(explode(' ', $parts['headers']) as $header) {
            if($header === '(request-target)') {
                $plaintext[] = sprintf('(request-target): %s %s', strtolower($_SERVER['REQUEST_METHOD']), $_SERVER['REQUEST_URI']);
                continue;
            }

            if(!isset($headers[$header])) {
                throw new \Exception('Unknown signature header: ' . $header);
            }

            $plaintext[] = sprintf('%s: %s', $header, $headers[$header]);
        }

        if (!empty($parts)) {
            $key = $this->fetchPublicKey($parts['keyId']);
            if(empty($key)) {
                return false;
            }

            return HttpSignature::verify(
                $parts['signature'],
                $key,
                implode("\n", $plaintext)
            );
        }

        return false;
    }

    private function fetchPublicKey($url): ?string
    {
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "Accept: application/json\r\n"
            ]
        ];

        $context = stream_context_create($opts);

        //@Todo cache key
        $user = file_get_contents(substr($url, 0, strpos($url, '#')), false, $context);
        if(empty($user)) {
            return null;
        }

        $json = @json_decode($user, true);

        if(empty($json) || empty($json['publicKey']['publicKeyPem'])) {
            return null;
        }

        return $json['publicKey']['publicKeyPem'];
    }

}
