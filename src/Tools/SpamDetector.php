<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Tools;

use DOMDocument;
use DOMXPath;
use Kniebes\VerifyUrl\Exception\SpamDetectedException;
use Kniebes\VerifyUrl\Model\Page;

class SpamDetector
{
    private const array SPAM_KEYWORDS = [
        'casino',
        'porn',
        'viagra',
        'sex',
        'slots',
        'gambling',
        'adult',
        'crypto',
        'bitcoin'
    ];

    private const int MIN_DISTINCT_KEYWORDS = 3;
    private const float MIN_HITS_PER_THOUSAND_WORDS = 5.0;
    private const string INVISIBLE_NODES_QUERY = '//script|//style|//noscript|//svg|//template|//comment()';

    /**
     * @throws SpamDetectedException
     */
    public static function detectSpam(Page $page): void
    {
        $titleKeywords = self::countKeywords($page->getTitle());

//        if ($titleKeywords !== []) {
//            throw new SpamDetectedException('Spam keywords in title: ' . self::formatKeywords($titleKeywords));
//        }

        $visibleText = self::extractVisibleText($page->getContent());
        $contentKeywords = self::countKeywords($visibleText);

        if (count($contentKeywords) < self::MIN_DISTINCT_KEYWORDS) {
            return;
        }

        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', $visibleText);
        $hitsPerThousandWords = $wordCount > 0 ? array_sum($contentKeywords) / $wordCount * 1000 : 0.0;

        if ($hitsPerThousandWords >= self::MIN_HITS_PER_THOUSAND_WORDS) {
            throw new SpamDetectedException('Spam keywords in content: ' . self::formatKeywords($contentKeywords));
        }
    }

    private static function countKeywords(string $text): array
    {
        $pattern = '/\b(' . implode('|', array_map(fn($keyword) => preg_quote($keyword, '/'), self::SPAM_KEYWORDS)) . ')\b/iu';

        if (!preg_match_all($pattern, $text, $keywordMatches)) {
            return [];
        }

        return array_count_values(array_map('strtolower', $keywordMatches[1]));
    }

    private static function extractVisibleText(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument();
        $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);

        foreach (iterator_to_array((new DOMXPath($document))->query(self::INVISIBLE_NODES_QUERY)) as $invisibleNode) {
            $invisibleNode->parentNode?->removeChild($invisibleNode);
        }

        return $document->textContent;
    }

    private static function formatKeywords(array $keywordCounts): string
    {
        return implode(', ', array_map(
            fn($keyword, $count) => $keyword . ' (' . $count . ')',
            array_keys($keywordCounts),
            $keywordCounts,
        ));
    }
}
