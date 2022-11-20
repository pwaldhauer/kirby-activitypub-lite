<?php

namespace ActivityPubLite\Storage;

interface Storage
{

    public function getAllFollowers(): array;
    public function removeFollower(array $actor): void;
    public function saveFollower(array $actor): void;
}