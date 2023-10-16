<?php

namespace MiniBus\Transactional;

interface SchemaManager
{
    /**
     * @param string $tableName
     */
    public function setUp($tableName);
}
