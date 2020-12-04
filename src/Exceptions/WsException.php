<?php

namespace AstrobinWs\Exceptions;

use Throwable;

/**
 * Class WsException
 * @package Astrobin\Exceptions
 */
class WsException extends \Exception
{

    /**
     * WsException constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code, ?Throwable $previous)
    {
        parent::__construct(...func_num_args());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return __CLASS__ . "[{$this->getCode()}]: {$this->getMessage()}\n";
    }
}
