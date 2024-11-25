<?php

declare(strict_types=1);

use PHPCore\SimpleTelegramLog\TGLog;

require_once dirname(__DIR__).'/vendor/autoload.php';

TGLog::debugLogMessage('This message will only be logged if debug mode is on');
