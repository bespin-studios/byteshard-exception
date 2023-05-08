<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard;

use byteShard\Internal\Exception\ExceptionInterface;
use byteShard\Internal\Exception\ExceptionTraits;
use JsonSerializable;
use Throwable;

/**
 * Class Exception
 * @package byteShard\Internal
 */
class Exception extends \Exception implements ExceptionInterface, JsonSerializable
{

    use ExceptionTraits;

    private string $query;
    private int    $apiCallLine;
    private string $apiCallFile;
    private string $ldapHost;
    private int    $ldapPort;
    private string $socketMessage;
    /** @deprecated */
    private string $idType;
    /** @deprecated */
    private string $idElement;
    /** @deprecated */
    private string $idRegularExpression;

    /**
     * Exception constructor.
     *
     * The Code for byteShard Exceptions should be made up like this:
     * Digit 1: 1 exceptions thrown by public functions, 2 exceptions thrown by internal functions
     * Digit 2-5: Unique ID for each class
     * Digit 6-8: incrementing exception code per class
     * @param string $message
     * @param int $code
     * @param string $method
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 10000000, string $method = '', Throwable $previous = null)
    {
        if ($method !== '') {
            $traces = $this->getTrace();
            foreach ($traces as $trace) {
                if (isset($trace['line'], $trace['file'], $trace['class']) && $trace['class'].'::'.$trace['function'] === $method) {
                    $this->apiCallLine = $this->line;
                    $this->apiCallFile = $this->file;
                    $this->line        = $trace['line'];
                    $this->file        = $trace['file'];
                    break;
                }
            }
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setLdapErrors(string $host, int $port, string $message): void
    {
        $this->ldapHost      = $host;
        $this->ldapPort      = $port;
        $this->socketMessage = $message;
    }

    public function setIdData(string $idType, string $idElement, ?string $regularExpression = null): void
    {
        $this->idType    = $idType;
        $this->idElement = $idElement;
        if ($regularExpression !== null) {
            $this->idRegularExpression = $regularExpression;
        }
    }
}
