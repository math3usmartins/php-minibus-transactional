<?php

namespace MiniBus\Transactional\Clock;

use MiniBus\Transactional\Clock;

final class SystemClock implements Clock
{
    public function timestamp()
    {
        return time();
    }
}
