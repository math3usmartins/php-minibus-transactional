<?php

namespace MiniBus\Transactional\Test\Handler;

use Exception;
use MiniBus\Envelope;
use MiniBus\Handler;

final class FailingHandler implements Handler
{
    /**
     * @var Exception
     */
    private $exception;

    public function __construct(Exception $exception)
    {
        $this->exception = $exception;
    }

    public function handle(Envelope $envelope)
    {
        throw $this->exception;
    }
}
