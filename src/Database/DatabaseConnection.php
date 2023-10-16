<?php

namespace MiniBus\Transactional\Database;

use Closure;
use Exception;

interface DatabaseConnection
{
    /**
     * @throws Exception
     */
    public function transactional(Closure $closure);
}
