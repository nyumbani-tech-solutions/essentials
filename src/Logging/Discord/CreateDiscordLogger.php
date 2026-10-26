<?php

namespace Nyumbani\Essentials\Logging\Discord;

use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Nyumbani\Essentials\Logging\RateLimitedHandler;

class CreateDiscordLogger
{
    /**
     * Build the logger of a channel that uses the discord driver, the way Laravel builds its slack one
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function __invoke(array $config): Logger
    {
        //  Read the level, which a record must reach to be sent
        $level = Logger::toMonologLevel($config['level'] ?? 'error');

        //  Send nothing when no webhook is set, so a machine without one still logs
        if (blank($config['url'] ?? null)) {
            return new Logger('discord', [new NullHandler($level)]);
        }

        //  Post the records to the webhook
        $handler = new DiscordWebhookHandler($config['url'], $config['username'] ?? config('app.name', 'Nyumbani'), $level);

        //  Throttle repeats, unless the throttle is switched off with false
        if (($config['throttle'] ?? []) !== false) {
            $handler = new RateLimitedHandler(
                $handler,
                'discord-notifications',
                (int) ($config['throttle']['attempts'] ?? 20),
                (int) ($config['throttle']['seconds'] ?? 60),
            );
        }

        //  Trim long messages to fit Discord
        $maxLength = (int) ($config['max_length'] ?? 3500);

        //  Truncate long messages
        $truncate = fn (LogRecord $record) => strlen($record->message) > $maxLength
            ? $record->with(message: str()->limit($record->message, $maxLength))
            : $record;

        //  Return the logger
        return new Logger('discord', [$handler], [$truncate]);
    }
}
