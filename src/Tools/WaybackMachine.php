<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Tools;

use GuzzleHttp\Exception\ConnectException;
use Kniebes\VerifyUrl\Model\Result;
use Throwable;

class WaybackMachine
{
    private const float MIN_SECONDS_BETWEEN_REQUESTS = 2.0;

    private static ?float $lastRequestAt = null;

    private static bool $isUnavailable = false;

    public static function findAlternative(string $url, Result $result): Result
    {
        if (self::$isUnavailable) {
            return $result->setIsValid(false)->addError('Wayback Machine: skipped, unavailable');
        }

        self::waitForNextRequest();

        try {
            $response = Client::getClient()->request(
                method: 'GET',
                uri: 'https://archive.org/wayback/available',
                options: ['query' => ['url' => $url]]
            );
        } catch (Throwable $throwable) {
            if (self::isTimeout($throwable)) {
                self::$isUnavailable = true;
            }

            return $result->setIsValid(false)->addError($throwable->getMessage());
        }

        if ($response->getStatusCode() === 429) {
            self::$isUnavailable = true;
        }

        if ($response->getStatusCode() !== 200) {
            return $result->setIsValid(false)->addError('Wayback Machine: Status Code ' . $response->getStatusCode());
        }

        $data = json_decode((string) $response->getBody(), true);
        if (!is_array($data)) {
            return $result->setIsValid(false)->addError('Wayback Machine: no json returned');
        }

        $closest = $data['archived_snapshots']['closest'] ?? null;

        if (($closest['available'] ?? false) === true && !empty($closest['url'])) {
            return $result->setIsValid(false)->setReplacement($closest['url']);
        }

        return $result->setIsValid(false)->setIsBroken(true)->addError('Wayback Machine: No snapshot found');
    }

    private static function isTimeout(Throwable $throwable): bool
    {
        return $throwable instanceof ConnectException
            && ($throwable->getHandlerContext()['errno'] ?? null) === CURLE_OPERATION_TIMEDOUT;
    }

    private static function waitForNextRequest(): void
    {
        if (self::$lastRequestAt !== null) {
            $secondsSinceLastRequest = microtime(true) - self::$lastRequestAt;

            if ($secondsSinceLastRequest < self::MIN_SECONDS_BETWEEN_REQUESTS) {
                usleep((int) ((self::MIN_SECONDS_BETWEEN_REQUESTS - $secondsSinceLastRequest) * 1000000));
            }
        }

        self::$lastRequestAt = microtime(true);
    }
}
