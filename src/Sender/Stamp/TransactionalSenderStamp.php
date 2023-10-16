<?php

namespace MiniBus\Transactional\Sender\Stamp;

use MiniBus\Envelope\Stamp;

final class TransactionalSenderStamp implements Stamp
{
    const NAME = 'transactional:sender';

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
