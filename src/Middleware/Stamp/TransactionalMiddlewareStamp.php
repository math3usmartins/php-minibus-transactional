<?php

namespace MiniBus\Transactional\Middleware\Stamp;

use MiniBus\Envelope\Stamp;

final class TransactionalMiddlewareStamp implements Stamp
{
    const NAME = 'transactional:middleware';

    public function name()
    {
        return self::NAME;
    }

    public function isEqualTo(Stamp $anotherStamp)
    {
        return ($anotherStamp instanceof self)
            && (self::NAME === $anotherStamp->name());
    }
}
