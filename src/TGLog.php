<?php

declare(strict_types=1);

namespace PHPCore\SimpleTelegramLog;

use Exception;
use TuriBot\Client;

class TGLog
{
    private static array $staticSelfs = [];

    private Client $client;

    public function __construct(
        private readonly string $botToken,
        private readonly int $chatId,
        private ?bool $debug = null
    ) {
        $this->client = new Client($this->botToken);
        $this->debug = null === $debug ?
            $_ENV['DEBUG'] ?? false
            : $debug;
    }

    public static function init(
        ?string $botToken = null,
        ?int $chatId = null,
        ?bool $debug = null
    ): self {
        $botToken = $botToken ?? ($_ENV['TG_LOG_BOT_TOKEN'] ?? null);
        if (empty($botToken)) {
            throw new Exception(__NAMESPACE__.' Bot Token is missing');
        }
        $chatId = $chatId ?? ($_ENV['TG_LOG_CHAT_ID'] ?? null);
        if (empty($chatId)) {
            throw new Exception(__NAMESPACE__.' Chat ID is missing');
        }
        $staticSelfKey = $botToken.'_'.$chatId;

        return self::$staticSelfs[$staticSelfKey]
            ?? self::$staticSelfs[$staticSelfKey] = new self(
                $botToken,
                $chatId,
                $debug
            );
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function debugDo(callable $do): void
    {
        if ($this->isDebug()) {
            $do($this);
        }
    }

    private static function getRequestDump(bool $asArray = false): array|string {
        $requestData = [
            'get'     => (empty($_GET) ? [] : $_GET),
            'post'    => (empty($_POST) ? [] : $_POST),
            'cookies' => (empty($_COOKIE) ? [] : $_COOKIE),
            'session' => (empty($_SESSION) ? [] : $_SESSION),
            'url'     => (empty($_SERVER) || empty($_SERVER['REQUEST_URI']) ? '-' : $_SERVER['REQUEST_URI']),
        ];
        return $asArray ? $requestData : json_encode($requestData, JSON_PRETTY_PRINT);
    }

    private function mode(): string
    {
        return ($this->isDebug() ? '[DEBUG' : '[PRODUCTION').' MODE]';
    }

    public function sendMessage(string $message): void
    {
        $this->client->sendMessage(
            $this->chatId,
            $this->mode().' - Message:'.PHP_EOL.$message,
        );
    }

    public static function logMessage(
        string $message,
        ?string $botToken = null,
        ?int $chatId = null,
    ): void {
        self::init($botToken, $chatId)
            ->sendMessage($message);
    }

    public static function debugLogMessage(
        string $message,
        ?string $botToken = null,
        ?int $chatId = null,
    ): void {
        self::init($botToken, $chatId)
            ->debugDo(fn(self $self) => $self->sendMessage($message));
    }

    public function sendException(
        \Throwable $exception,
        bool $stacktrace = true,
        bool $requestData = false
    ): void
    {
        $this->sendMessage(
            'Exception'.PHP_EOL
            .$exception->getMessage().' on line '.
            $exception->getLine().' in file '.
            $exception->getFile()
            . ($stacktrace
                ? PHP_EOL.PHP_EOL.'stacktrace: '.$exception->getTraceAsString()
                : ''
            )
        );
        if ($requestData) {
            $this->sendMessage(
                'Request Data:'.PHP_EOL
                .self::getRequestDump()
            );
        }
    }

    public static function logException(
        \Throwable $exception,
        bool $stacktrace = true,
        bool $requestData = false,
        ?string $botToken = null,
        ?int $chatId = null,
    ): void {
        self::init($botToken, $chatId)->sendException($exception, $stacktrace, $requestData);
    }

    public static function debugLogException(
        \Throwable $exception,
        bool $stacktrace = true,
        bool $requestData = false,
        ?string $botToken = null,
        ?int $chatId = null,
    ): void
    {
        self::init($botToken, $chatId)
            ->debugDo(fn(self $self) => $self->sendException($exception, $stacktrace, $requestData));
    }

    public function sendRequestDump(): void
    {
        $this->sendMessage(
            'REQUEST DUMP:'.PHP_EOL
            .self::getRequestDump()
        );
    }


    public static function logRequestDump(
        ?string $botToken = null,
        ?int $chatId = null,
    ): void
    {
        self::init($botToken, $chatId)->sendRequestDump();
    }

    public static function debugLogRequestDump(
        ?string $botToken = null,
        ?int $chatId = null,
    ): void
    {
        self::init($botToken, $chatId)->debugDo(fn (self $self) => $self->sendRequestDump());
    }
}
