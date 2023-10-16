<?php

namespace MiniBus\Transactional\Test;

use MiniBus\Transactional\Clock;

final class StubClock implements Clock
{
    /**
     * @var int
     */
    private $timestamp;

    /**
     * @param int $timestamp
     */
    public function __construct($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function timestamp()
    {
        return $this->timestamp;
    }
}
