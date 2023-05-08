<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Exception;

use byteShard\Enum\LogLevel;

/**
 * Interface ExceptionInterface
 * @package byteShard\Internal\Exception
 */
interface ExceptionInterface
{
    public function getClientMessage(): string;

    public function getLogChannel(): string;

    public function getLogLevel(): LogLevel;

    public function getStackTrace(): array;

    /**
     * if a client message is set, the locale token won't be used
     */
    public function setClientMessage(string $message): void;

    public function setCode(int $code): void;

    public function setInfo(mixed ...$info): void;

    public function setLocaleToken(string $token): void;

    public function setLogChannel(string $channel): void;

    /**
     * the log level will determine if the exception is pushed to a log file or not
     */
    public function setLogLevel(LogLevel $level): void;

    public function setMessage(string $message): void;

    public function setTrace(array $trace): void;
}
