<?php

declare(strict_types=1);

namespace PHPCore\SimpleTelegramLog;

class TGLog
{
    public const API_BASE_URL = 'https://api.telegram.org';
    private static array $staticSelfs = [];

    public function __construct(
        private readonly string $botToken,
        private readonly int|string $chatId,
        private ?bool $debug = null,
        private readonly string $apiBaseUrl = self::API_BASE_URL,
        private readonly ?string $httpExecFunction = null
    ) {
        $this->debug = null === $debug ?
            boolval($_ENV['DEBUG'] ?? false)
            : $debug;
    }

    public static function init(
        ?string $botToken = null,
        ?int $chatId = null,
        ?bool $debug = null,
        string $apiBaseUrl = self::API_BASE_URL,
        ?string $httpExecFunction = null
    ): self {
        $botToken = $botToken ?? ($_ENV['TG_LOG_BOT_TOKEN'] ?? null);
        if (empty($botToken)) {
            throw new \RuntimeException(__NAMESPACE__.' Bot Token is missing');
        }
        $chatId = $chatId ?? ($_ENV['TG_LOG_CHAT_ID'] ?? null);
        if (empty($chatId)) {
            throw new \RuntimeException(__NAMESPACE__.' Chat ID is missing');
        }
        $staticSelfKey = $botToken.'_'.$chatId;

        return self::$staticSelfs[$staticSelfKey]
            ?? self::$staticSelfs[$staticSelfKey] = new self(
                $botToken,
                $chatId,
                $debug,
                $apiBaseUrl,
                $httpExecFunction
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
            'files'    => (empty($_FILES) ? [] : $_FILES),
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

    private function getApiBaseUrl(): string
    {
        return $_ENV['TG_LOG_BOT_SERVER_URL'] ?? $this->apiBaseUrl;
    }

    private function getHttpExecFunction(): ?string
    {
        return $_ENV['TG_LOG_BOT_HTTP_CMD'] ?? $this->httpExecFunction;
    }

    private function sendFastRequest(string $url): void
    {
        if (empty($execFunction = $this->getHttpExecFunction())) {
            $parts = parse_url($url);
            $isSsl = $parts['scheme'] === 'https';
            $fp = fsockopen(
                ($isSsl ? 'ssl://' : '').$parts['host'],
                $parts['port'] ?? ($isSsl ? 443 : 80),
                $errorCode,
                $errorMessage,
                30
            );
            $out = 'GET '.$parts['path']
                .(empty($parts['query']) ? '' : '?'.$parts['query'])
                .' HTTP/1.1'."\r\n";
            $out .= 'Host: '.$parts['host']."\r\n";
            $out .= 'Connection: Close'."\r\n\r\n";

            fwrite($fp, $out);
            fgets($fp, 2);
            fclose($fp);
        } else {
            exec($execFunction.' "'.$url.'" > /dev/null 2>&1 &');
        }
    }

    public function sendMessage(string $message): void
    {
        $maxMessageLength = 4096;
        $fullMessage = '<i>'.$this->mode().'</i>'.PHP_EOL.$message;
        foreach (str_split($fullMessage, $maxMessageLength) as $messagePart) {
            $this->sendFastRequest(
                $this->getApiBaseUrl().'/bot'.$this->botToken.'/sendMessage?'
                .http_build_query([
                    'chat_id'    => $this->chatId,
                    'text'       => $messagePart,
                    'parse_mode' => 'HTML',
                ])
            );
        }
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
