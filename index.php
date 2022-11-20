<?php

require 'vendor/autoload.php';

use ActivityPubLite\Controller\ActivityPubController;
use ActivityPubLite\Controller\WebfingerController;
use ActivityPubLite\Requests\CreateNoteActivityPubRequest;
use ActivityPubLite\Services\RequestService;
use ActivityPubLiteKirby\Util\KirbyActivityPubConfig;

function apl_send_create_to_followers(string $content, ?string $url = null): void
{
    $config = new KirbyActivityPubConfig();
    $service = new RequestService($config);

    $id = sprintf('https://%s/_aplite/user/%s/%s', $config->host, $config->handle, uniqid());

    $request = new CreateNoteActivityPubRequest(
        config: $config,
        published_at: new \DateTime(),
        content: $content,
        to: null, // Gets filled by Service
        id: $id,
        url: $url ?? null
    );

    $service->sendRequestToFollowers($request);
}


Kirby::plugin('pwaldhauer/kirby-activitypub-lite', [

    'hooks' => [
        'page.update:after' => function (\Kirby\Cms\Page $newPage, \Kirby\Cms\Page $oldPage) {
            if ($oldPage->activityPubPosted()->bool() === true) {
                return;
            }

            if ($newPage->activityPubPosted()->bool() === true || $newPage->activityPubText()->isEmpty()) {
                return;
            }

            apl_send_create_to_followers(
                $newPage->activityPubText()->kirbyText() .
                sprintf('<a href="%s">%s</a>', $newPage->url(), $newPage->url()),
                $newPage->url()
            );

            kirby()->impersonate('kirby');
            $newPage->update([
                'activityPubPosted' => true
            ]);
        },

    ],

    'routes' => function ($kirby) {
        $config = new KirbyActivityPubConfig();

        return [
            [
                'pattern' => '.well-known/webfinger',
                'action' => function () use ($config) {
                    return (new WebfingerController($config))->webfinger(
                        get('resource')
                    );
                }
            ],


            ['pattern' => '_aplite/inbox',
                'method' => 'POST',
                'action' => function () use ($config) {
                    return (new ActivityPubController($config))->inbox(
                        json_decode(file_get_contents('php://input'), true)
                    );
                }
            ],


            ['pattern' => '_aplite/user/' . $config->handle . '/inbox',
                'method' => 'POST',
                'action' => function () use ($config) {
                    $input = file_get_contents('php://input');
                    return (new ActivityPubController($config))->inbox(
                        json_decode($input, true)
                    );
                }
            ],

            ['pattern' => '_aplite/user/' . $config->handle . '/followers',
                'method' => 'GET',
                'action' => function () use ($config) {
                    return (new ActivityPubController($config))->followers();
                }
            ],

            [
                'pattern' => '_aplite/user/' . $config->handle,
                'action' => function () use ($config) {
                    return (new ActivityPubController($config))->user();
                }
            ]
        ];
    }
]);
