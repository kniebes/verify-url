<?php declare(strict_types=1);

namespace Kniebes\VerifyUrl\Exception;

class InvalidStatusException extends \Exception
{
    public function __construct(private int $status)
    {

        parent::__construct('Invalid Status: ' . $status);
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
