<?php
/**
 * Copyright © Bazaarvoice, Inc. All rights reserved.
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Bazaarvoice\Connector\Logger;

use Bazaarvoice\Connector\Api\ConfigProviderInterface;
use DateTimeZone;
use Exception;
use Magento\Framework\App\State;
use Monolog\Handler\HandlerInterface;
use Monolog\JsonSerializableDateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Stringable;

/**
 * Class Logger
 *
 * @package Bazaarvoice\Connector\Logger
 */
class Logger extends \Monolog\Logger
{
    /**
     * @var bool
     */
    protected $admin = false;
    /**
     * @var ConfigProviderInterface
     */
    private $configProvider;

    /**
     * Logger constructor.
     *
     * @param string                                    $name
     * @param ConfigProviderInterface                   $configProvider
     * @param State                                     $state
     * @param list<HandlerInterface>                    $handlers Optional stack of handlers, the first one in the array is called first, etc.
     * @param callable[]                                $processors Optional array of processors
     * @param DateTimeZone|null                         $timezone Optional timezone, if not provided date_default_timezone_get() will be used
     *
     * @phpstan-param array<(callable(LogRecord): LogRecord)|ProcessorInterface> $processors
 */
    public function __construct(
        string $name,
        ConfigProviderInterface $configProvider,
        State $state,
        array $handlers = [],
        array $processors = [],
        DateTimeZone|null $timezone = null
    ) {
        try {
            $this->admin = $state->getAreaCode() === 'adminhtml';
        } catch (Exception $e) {
        }
        parent::__construct($name, $handlers);
        $this->configProvider = $configProvider;
        /** @codingStandardsIgnoreEnd */
    }

    /**
     * Adds a log record at the DEBUG level.
     *
     * This method allows for compatibility with common interfaces.
     *
     * @param string|Stringable $message The log message
     * @param mixed[]           $context The log context
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        if ($this->configProvider->isDebugEnabled()) {
            if (is_array($message)) {
                $message = json_encode($message);
            }
            $this->addRecord(Level::DEBUG, strval($message),$context);
        }
    }

    /**
     * Adds a log record.
     *
     * @param  int|Level              $level    The logging level (a Monolog or RFC 5424 level)
     * @param  string                 $message  The log message
     * @param  mixed[]                $context  The log context
     * @param  JsonSerializableDateTimeImmutable|null $datetime Optional log date to log into the past or future
     *
     * @return bool                   Whether the record has been processed
     *
     * @phpstan-param value-of<Level::VALUES>|Level $level
     */
    public function addRecord(int|Level $level, string $message, array $context = [], JsonSerializableDateTimeImmutable|null $datetime = null): bool
    {
        if (is_array($message)) {
            $message = print_r($message, $return = true);
        }

        if (php_sapi_name() == "cli" || $this->admin) {
            print_r($message."\n");
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }
}
