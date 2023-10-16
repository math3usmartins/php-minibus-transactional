<?php

namespace MiniBus\Transactional\Receiver\Stamp;

use MiniBus\Envelope\Stamp;

final class TransactionalReceiverStamp implements Stamp
{
    const NAME = 'transactional:receiver';

    /**
     * @var int
     */
    private $id;

    /**
     * @param int $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    public function name()
    {
        return self::NAME;
    }

    public function id()
    {
        return $this->id;
    }

    public function isEqualTo(Stamp $anotherStamp)
    {
        return ($anotherStamp instanceof self)
            && ($anotherStamp->id === $this->id);
    }
}
