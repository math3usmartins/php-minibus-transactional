<?php

namespace MiniBus\Transactional\Database\Exception;

use Exception;
use MiniBus\Envelope;

final class FailedToAckEnvelope extends Exception
{
    /**
     * @var Envelope
     */
    private $envelope;

    public static function fromEnvelope(Envelope $envelope)
    {
        $exception = new self('Failed to ack envelope');
        $exception->envelope = $envelope;

        return $exception;
    }

    /**
     * @return Envelope
     */
    public function envelope()
    {
        return $this->envelope;
    }
}
