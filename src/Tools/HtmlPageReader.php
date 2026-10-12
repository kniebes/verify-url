<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Tools;

use GuzzleHttp\Psr7\Utils;
use Kniebes\VerifyUrl\Exception\InvalidContentTypeException;
use Kniebes\VerifyUrl\Model\Page;
use Psr\Http\Message\ResponseInterface;


class HtmlPageReader
{
    private const int MAX_BODY_BYTES = 5242880;
    private const array HTML_MIME_TYPES = ['text/html', 'application/xhtml+xml'];

    /**
     * @param ResponseInterface $response
     * @return Page
     * @throws InvalidContentTypeException
     */
    public static function read(ResponseInterface $response): Page
    {
        $body = $response->getBody();

        if (!self::isHtml($response)) {
            $body->close();

            throw new InvalidContentTypeException();
        }

        $content = Utils::copyToString(stream: $body, maxLen: self::MAX_BODY_BYTES);
        $body->close();

        return new Page(title: self::extractTitle($content), content: $content);
    }

    private static function isHtml(ResponseInterface $response): bool
    {
        $contentType = $response->getHeaderLine('Content-Type');

        if ($contentType === '') {
            return true;
        }

        $mimeType = strtolower(trim(explode(';', $contentType)[0]));

        return in_array($mimeType, self::HTML_MIME_TYPES, true);
    }

    private static function extractTitle(string $content): string
    {
        if (preg_match('/<title\b[^>]*>(.*)<\/title>/siU', $content, $titleMatches) !== 1) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', html_entity_decode($titleMatches[1])));
    }
}
