<?php
/**
 * @copyright  Copyright (c) 2009 Bespin Studios GmbH
 * @license    See LICENSE file that is distributed with this source code
 */

namespace byteShard\Internal\Exception;

use byteShard\Enum\LogLevel;
use byteShard\Locale;
use ReflectionClass;
use ReflectionException;

trait ExceptionTraits
{
    protected string   $client_locale_token         = 'byteShard::exception.exception.undefinedLocaleToken';
    protected string   $client_message              = '';
    protected bool     $hide_all_function_args      = false;
    protected array    $hide_functions_args         = [];
    protected array    $hide_functions_args_indices = [];
    protected string   $log_channel                 = 'byteShard';
    protected LogLevel $log_level                   = LogLevel::ERROR;
    protected array    $info                        = [];
    private array      $internalExceptions          = [];

    /**
     * @param array $trace
     */
    public function setTrace(array $trace): void
    {
        try {
            $ref        = new ReflectionClass($this);
            $properties = $ref->getParentClass()->getProperty('trace');
            $properties->setValue($this, $trace);
        } catch (ReflectionException $e) {
            $this->internalExceptions[] = $e;
        }
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @param int $code
     */
    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * @param string $token
     */
    public function setLocaleToken(string $token): void
    {
        $this->client_locale_token = $token;
    }

    /**
     * if a client message is set, the locale token won't be used
     * @param string $message
     */
    public function setClientMessage(string $message): void
    {
        $this->client_message = $message;
    }

    /**
     * @return string
     */
    public function getClientMessage(): string
    {
        if ($this->client_message !== '') {
            return $this->client_message;
        }
        return Locale::get($this->client_locale_token);
    }

    /**
     * @param string $channel
     */
    public function setLogChannel(string $channel): void
    {
        $this->log_channel = $channel;
    }

    /**
     * @return string
     */
    public function getLogChannel(): string
    {
        return $this->log_channel;
    }

    /**
     * the log level will determine if the exception is pushed to a log file or not
     * @param LogLevel $level
     */
    public function setLogLevel(LogLevel $level): void
    {
        $this->log_level = $level;
    }

    /**
     * @return LogLevel
     */
    public function getLogLevel(): LogLevel
    {
        return $this->log_level;
    }

    /**
     * @param mixed ...$info
     */
    public function setInfo(mixed ...$info): void
    {
        foreach ($info as $val) {
            $this->info[] = $val;
        }
    }

    /**
     * @return array
     */
    public function getStackTrace(): array
    {
        return $this->discloseCredentials(true);
    }

    /**
     * mark the debug backtrace as CONFIDENTIAL
     */
    public function setTraceLogConfidential(): void
    {
        $this->hide_all_function_args = true;
    }

    /**
     * mark the args of supplied functions as CONFIDENTIAL
     * @param string ...$function_names
     */
    public function setTraceLogFunctionArgumentsConfidential(string ...$function_names): void
    {
        foreach ($function_names as $function_name) {
            $this->hide_functions_args[] = $function_name;
        }
    }

    /**
     * mark a specific argument in a specific function as CONFIDENTIAL
     * usage:
     * setTraceLogFunctionArgumentsIndexConfidential(['__construct', 2], ['setPassword' => 1]);
     * @param array ...$function_names_index_array
     */
    public function setTraceLogFunctionArgumentsIndexConfidential(array ...$function_names_index_array): void
    {
        foreach ($function_names_index_array as $function_name_index_array) {
            foreach ($function_name_index_array as $function => $index) {
                $this->hide_functions_args_indices[$function][$index] = true;
            }
        }
    }

    /**
     * function to modify the stack trace if needed
     */
    public function __debugInfo(): array
    {
        return $this->discloseCredentials();
    }

    public function jsonSerialize(): array
    {
        return $this->__debugInfo();
    }

    /**
     * @param bool $only_trace
     * @return array
     */
    private function discloseCredentials(bool $only_trace = false): array
    {
        if ($only_trace === false) {
            if ($this->hide_all_function_args === false && empty($this->hide_functions_args) && empty($this->hide_functions_args_indices)) {
                unset($this->hide_functions_args, $this->hide_all_function_args, $this->hide_functions_args_indices);
                return (array)$this;
            }
            if (defined('DISCLOSE_CREDENTIALS') && DISCLOSE_CREDENTIALS === true && defined('LOGLEVEL') && LOGLEVEL === LogLevel::DEBUG) {
                unset($this->hide_functions_args, $this->hide_all_function_args, $this->hide_functions_args_indices);
                return (array)$this;
            }
            if ($this->hide_all_function_args === true) {
                try {
                    $ref        = new ReflectionClass($this);
                    $properties = $ref->getParentClass()->getProperty('trace');
                    $properties->setValue($this, 'CONFIDENTIAL');
                } catch (ReflectionException $e) {
                    $this->internalExceptions[] = $e;
                }
                unset($this->hide_functions_args, $this->hide_all_function_args, $this->hide_functions_args_indices);
                return (array)$this;
            }
        }
        if (!empty($this->hide_functions_args) || !empty($this->hide_functions_args_indices)) {
            $trace = $this->getTrace();
            foreach ($trace as $key => $val) {
                if (array_key_exists('function', $val) && array_key_exists('args', $val)) {
                    foreach ($this->hide_functions_args as $hide_functions_arg) {
                        if ($val['function'] === $hide_functions_arg) {
                            $trace[$key]['args'] = 'CONFIDENTIAL';
                        }
                    }
                    foreach ($this->hide_functions_args_indices as $function => $indices) {
                        if ($val['function'] === $function) {
                            foreach ($indices as $index => $nil) {
                                if (array_key_exists($index, $trace[$key]['args'])) {
                                    $trace[$key]['args'][$index] = 'CONFIDENTIAL';
                                }
                            }
                        }
                    }
                }
            }
            if ($only_trace === true) {
                return $trace;
            }
            try {
                $ref        = new ReflectionClass($this);
                $properties = $ref->getParentClass()->getProperty('trace');
                $properties->setValue($this, $trace);
            } catch (ReflectionException $e) {
                $this->internalExceptions[] = $e;
            }
            unset($this->hide_functions_args, $this->hide_all_function_args, $this->hide_functions_args_indices);
            return (array)$this;
        }
        if ($only_trace === true) {
            return $this->getTrace();
        }
        unset($this->hide_functions_args, $this->hide_all_function_args, $this->hide_functions_args_indices);
        return (array)$this;
    }
}
