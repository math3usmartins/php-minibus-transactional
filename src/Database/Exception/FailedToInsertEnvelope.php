<?php

namespace MiniBus\Transactional\Database\Exception;

use Exception;
use MiniBus\Envelope;

final class FailedToInsertEnvelope extends Exception
{
    /**
     * @var Envelope
     */
    private $envelope;

    public static function fromEnvelope(Envelope $envelope)
    {
        $exception = new self('Failed to insert envelope');
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
