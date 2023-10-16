<?php

namespace MiniBus\Transactional;

interface Clock
{
    /**
     * @return int
     */
    public function timestamp();
}
