<?php

namespace Nayemuf\SteadfastCourier\Exceptions;

use Exception;

class SteadfastException extends Exception
{
    /**
     * @var array
     */
    protected $errors;

    /**
     * SteadfastException constructor.
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     */
    public function __construct(string $message = "", int $code = 0, array $errors = [])
    {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Get detailed errors array
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

