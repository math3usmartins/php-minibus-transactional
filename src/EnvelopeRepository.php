<?php

namespace MiniBus\Transactional;

use Exception;
use MiniBus\Envelope;
use MiniBus\Envelope\EnvelopeCollection;
use MiniBus\Transactional\Database\Exception\FailedToInsertEnvelope;

interface EnvelopeRepository
{
    /**
     * @throws Exception
     * @throws FailedToInsertEnvelope
     */
    public function insert(Envelope $envelope);

    /**
     * @param int $limit
     *
     * @return EnvelopeCollection
     */
    public function find($limit);

    /**
     * @param int $id
     */
    public function update($id, Envelope $envelope);

    /**
     * @param int[] $idValues
     */
    public function deleteById($idValues);
}
