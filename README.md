# kirby-activitypub-lite

A very lightweight (and incomplete) implementation of the ActivityPub protocol for use with the Kirby CMS.

It's a preview version, please only use for reference.

## Current features

- Register new followers / remove followers
- Create a new activity for pages with a `activityPubText` field in the blueprint

### Limitations

- Currently only the `page.update:after` hook is used
- Currently, there is no queue mechanism -- all activities are send out synchronously. So saving a page may take a lot of time, if your page has a lot of followers on different activitypub instances.
- The signature verification is not complete and the date is not checked

## Setup

Install via `composer require pwaldhauer/kirby-activitypub-lite`.

Create a keypair and place it somewhere (e.g. besides the `config.php`)

```shell
openssl genrsa -out private.pem 2048
openssl rsa -in private.pem -outform PEM -pubout -out public.pem
```

Add the following lines to your `config.php`:

```php
    'activitypub-lite' => [
        // complete handle will be: test@example.com
        'host' => 'example.com',
        'handle' => 'test',
        'displayName' => 'My nice Kirby blog',

        'profileOptions' => [
            'summary' => 'Profile summary',
            'url' => 'https://knuspermagier.de',
            'publishedDate' => '2017-04-05T00:00:00Z', // shown as "Registered at"
            'icon' => [
                'type' => 'Image',
                'mediaType' => 'image/png',
                'url' => 'https://knuspermagier.de/activity-pub-icon.png',
            ],
        ],

        'keypair' => [
            'publicKey' => __DIR__ . '/public.pem',
            'privateKey' => __DIR__ . '/private.pem',
        ],

        'storage' => \ActivityPubLiteKirby\Storage\KirbyFilesystemStorage::class
    ],
```

Add fields to a blueprint:

```yaml
  activityPubText:
    type: textarea
  activityPubPosted:
    type: toggle
    default: false
```

All pages that contain an `activityPubText` will be posted to your followers.