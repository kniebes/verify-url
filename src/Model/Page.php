<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Model;

class Page
{
    public function __construct(
        private readonly string $title,
        private readonly string $content,
    )
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getContent(): string
    {
        return $this->content;
    }
}
