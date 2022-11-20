<?php

namespace ActivityPubLiteKirby\Storage;


use ActivityPubLite\Storage\Storage;

class KirbyFilesystemStorage implements Storage
{
    public function getAllFollowers(): array
    {
        return array_map(function ($file) {
            return json_decode(file_get_contents($file), true);
        }, glob($this->contentDir() . '/*.json'));
    }

    public function removeFollower(array $actor): void
    {
        @unlink($this->filenameForActor($actor));
    }

    public function saveFollower(array $actor): void
    {
        file_put_contents(
            $this->filenameForActor($actor),
            json_encode($actor)
        );
    }


    private function filenameForActor(array $actor): string
    {
        return sprintf('%s/%s', $this->contentDir(), sha1($actor['actor']) . '.json');
    }


    private function contentDir(): string
    {
        $contentDir = (\kirby()->root('content') . '/.aplite');
        if (!is_dir($contentDir)) {
            mkdir($contentDir);
        }

        return $contentDir;
    }
}