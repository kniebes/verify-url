<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl;

use GuzzleHttp\Exception\GuzzleException;
use Kniebes\VerifyUrl\Exception\InvalidContentTypeException;
use Kniebes\VerifyUrl\Exception\InvalidStatusException;
use Kniebes\VerifyUrl\Exception\SpamDetectedException;
use Kniebes\VerifyUrl\Model\Page;
use Kniebes\VerifyUrl\Model\Result;
use Kniebes\VerifyUrl\Tools\Client;
use Kniebes\VerifyUrl\Tools\HtmlPageReader;
use Kniebes\VerifyUrl\Tools\SpamDetector;
use Kniebes\VerifyUrl\Tools\WaybackMachine;

class VerifyUrl
{
    public function verify(string $url): Result
    {
        try {
            $page = $this->fetchPage($url);
            SpamDetector::detectSpam($page);

            return new Result(isValid: true);

        } catch (GuzzleException | SpamDetectedException | InvalidStatusException $exception) {
            return WaybackMachine::findAlternative(
                url: $url,
                result: (new Result(false))->addError($exception->getMessage())
            );

        } catch (InvalidContentTypeException) {
            return (new Result(isValid: true))->addError('Invalid content type '.$url);
        }
    }

    /**
     * @throws GuzzleException
     * @throws InvalidContentTypeException
     * @throws InvalidStatusException
     */
    protected function fetchPage(string $url): Page
    {
        $response = Client::getClient()->request(
            method: 'GET',
            uri: $url,
            options: [
                'allow_redirects' => [
                    'max' => 5,
                    'strict' => true,
                    'referer' => true,
                    'protocols' => ['https', 'http'],
                    'track_redirects' => true,
                ],
                'stream' => true,
            ]
        );

        if ($response->getStatusCode() >= 400) {
            throw new InvalidStatusException($response->getStatusCode());
        }

        return HtmlPageReader::read($response);
    }


}
