<?php

namespace WebBestPractice\Posts\Exceptions;

use RuntimeException;

class ColumnNotFoundException extends RuntimeException
{
    public function __construct(?string $column)
    {
        parent::__construct("Column '{$column}' not found.");
    }
}
