<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Tools;

class Client
{
    private static ?\GuzzleHttp\Client $client = null;

    public static function getClient(
        ?string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36',
        ?int $connectTimeout = 3,
        ?int $readTimeout = 5,
        ?int $timeout = 8,
        ?bool $isFresh = false
    ): \GuzzleHttp\Client
    {
        if (null === self::$client || $isFresh) {
            self::$client = new \GuzzleHttp\Client([
                'connect_timeout' => $connectTimeout,
                'read_timeout' => $readTimeout,
                'timeout' => $timeout,
                'http_errors' => false,
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept' => 'text/html,application/xhtml+xml,*/*;q=0.8',
                ],
            ]);
        }

        return self::$client;
    }
}
