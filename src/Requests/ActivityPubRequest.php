<?php

namespace ActivityPubLite\Requests;

use ActivityPubLite\Util\ActivityPubConfig;
use ActivityPubLite\Util\HttpSignature;

abstract class ActivityPubRequest
{

    protected array $data = [];

    public function __construct(
        protected ActivityPubConfig $config
    )
    {
    }

    public abstract function build();

    public function sendRequest($host, $path): void
    {
        $data = json_encode($this->data);

        $date = gmdate('D, d M Y H:i:s T', time());
        $digest = HttpSignature::digest($data);

        $signature = HttpSignature::sign(
            $this->config->privateKeyString(),
            $path,
            $host,
            $date,
            $digest);

        $signatureHeader = sprintf(
            'keyId="%s",headers="(request-target) host date digest",signature="%s"',
            $this->config->keyName(),
            base64_encode($signature)
        );

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, sprintf('https://%s%s', $host, $path));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $headers = [
            'Content-Type: application/json',
            'Date: ' . $date,
            'Signature: ' . $signatureHeader,
            'Digest: ' . $digest
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        //  DEBUG
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $server_output = curl_exec($ch);

        curl_close($ch);
    }

}