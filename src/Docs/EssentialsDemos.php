<?php

namespace Nyumbani\Essentials\Docs;

use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Monolog\Level;
use Monolog\LogRecord;
use Nyumbani\Docs\Docs;
use Nyumbani\Essentials\Casts\CleanName;
use Nyumbani\Essentials\Logging\Discord\DiscordWebhookHandler;
use Nyumbani\Essentials\Rules\KenyanNumberValid;
use Nyumbani\Essentials\Rules\PhoneNumberValid;
use Nyumbani\Essentials\Services\DateFilterService;
use Nyumbani\Essentials\Traits\HasKenyans;
use Throwable;

/**
 * The interactive demos of the Essentials docs, each running the real helper, rule or service on what a reader typed
 *
 * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
 */
class EssentialsDemos
{
    /**
     * Parse a period the way $request->period() does
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function period(array $input): array
    {
        //  Read the typed period and the default
        $typed = trim($input['period'] ?? '');
        $default = in_array($input['default'] ?? '', ['day', 'month', 'year'], true) ? $input['default'] : 'month';

        //  Parse it with the service behind the macro
        $period = app(DateFilterService::class)->parse($typed, $default);

        return [
            Docs::row('Read as', $period->formatted),
            Docs::row('Fell back to the default', $typed !== $period->formatted, $typed === $period->formatted),
            Docs::row('from', $period->from->format('Y-m-d H:i:s')),
            Docs::row('to', $period->to->format('Y-m-d H:i:s')),
            Docs::row('year', $period->year),
            Docs::row('month', $period->month),
        ];
    }

    /**
     * Format an amount with the money helpers
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function money(array $input): array
    {
        //  Read the amount and the total
        $amount = $input['amount'] ?? '';
        $total = (float) ($input['total'] ?? 0);

        //  Leave something that is not a number as it is
        if (! is_numeric($amount)) {
            return [Docs::row('numeric()', numeric($amount), false), Docs::row('Why', 'Not a number, so the helpers return it unchanged')];
        }

        return [
            Docs::row('money(amount)', money((float) $amount)),
            Docs::row('money(amount, false)', money((float) $amount, false)),
            Docs::row('numeric(amount)', numeric($amount)),
            Docs::row('numeric(amount, 0)', numeric($amount, 0)),
            Docs::row('percentage(amount, total)', percentage($amount, $total)),
            Docs::row('currency()', currency()),
        ];
    }

    /**
     * Format a date with the date helpers
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function dates(array $input): array
    {
        //  Read the typed text, or else the picked date and time
        $date = filled($input['text'] ?? null) ? $input['text'] : ($input['date'] ?? '');

        return [
            Docs::row('format_date()', format_date($date)),
            Docs::row('format_time(date, 12)', format_time($date)),
            Docs::row('format_time(date, 24)', format_time($date, 24)),
            Docs::row('format_date_time(date, 12)', format_date_time($date)),
            Docs::row('format_date_time(date, 24)', format_date_time($date, 24)),
            Docs::row('format_humans()', format_humans($date)),
        ];
    }

    /**
     * Format text and minutes with the text helpers
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function text(array $input): array
    {
        return [
            Docs::row('prettify(text)', prettify($input['text'] ?? '')),
            Docs::row('format_minutes(minutes)', format_minutes((int) ($input['minutes'] ?? 0))),
        ];
    }

    /**
     * Check a number against both phone rules
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function phoneRules(array $input): array
    {
        //  Read the number
        $number = $input['number'] ?? '';

        //  Check each rule
        $kenyan = $this->passes(new KenyanNumberValid, $number);
        $international = $this->passes(new PhoneNumberValid, $number);

        return [
            Docs::row('KenyanNumberValid', $kenyan ? 'Passes' : 'Fails', $kenyan),
            Docs::row('PhoneNumberValid', $international ? 'Passes' : 'Fails', $international),
        ];
    }

    /**
     * Clean a name the way the CleanName cast does on the way in
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function cleanName(array $input): array
    {
        //  Run the cast on a throwaway model
        $stored = (new CleanName)->set(new class extends Model {}, 'name', $input['name'] ?? '', []);

        return [Docs::row('Stored as', $stored)];
    }

    /**
     * Generate the Kenyan name, number and M-Pesa code the HasKenyans trait gives a factory
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function kenyans(array $input): array
    {
        //  Use the trait on a throwaway class
        $factory = new class
        {
            use HasKenyans;
        };

        //  Generate a number and check it against the Kenyan rule
        $phone = $factory->generateKenyanPhoneNumber();

        return [
            Docs::row('generateKenyanName()', $factory->generateKenyanName()),
            Docs::row('generateKenyanPhoneNumber()', $phone),
            Docs::row('generateMpesaTransactionCode()', $factory->generateMpesaTransactionCode()),
            Docs::row('Length', strlen($phone).' digits (a real number has 12)'),
            Docs::row('Passes KenyanNumberValid', $this->passes(new KenyanNumberValid, $phone) ? 'Yes' : 'No: on purpose, so it never reaches a real person', false),
        ];
    }

    /**
     * Show what the model traits and the enum trait give for the typed values
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function modelTraits(array $input): array
    {
        //  Read the amount and the enum case
        $amount = (float) ($input['amount'] ?? 0);
        $status = DemoStatus::tryFrom($input['status'] ?? '') ?? DemoStatus::PAID;

        //  Make up a customer with a ULID, as HasUlids would on create
        $customer = (new DemoCustomer)->forceFill(['ulid' => strtolower((string) str()->ulid())]);

        return [
            Docs::row('HasMoney: money(amount)', $customer->money($amount)),
            Docs::row('HasMoney: relativeMoney(amount)', $customer->relativeMoney($amount)),
            Docs::row('HasEnumElements: label()', $status->label()),
            Docs::row('HasEnumElements: lowerLabel()', $status->lowerLabel()),
            Docs::row('HasUlids: getRouteKey()', $customer->getRouteKey()),
            Docs::row('HasMorphRouteKey: getMorphRouteKey()', $customer->getMorphRouteKey()),
        ];
    }

    /**
     * Send a log record through the app's discord channel when it is set up, then show the embed it becomes
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function discord(array $input): array
    {
        //  Read what the reader chose
        $level = Level::fromName($input['level'] ?? 'Error');
        $message = $input['message'] ?? '';

        //  Send it for real, before the HTTP client is faked for the preview
        $sent = $this->sendToDiscord($level, $message);

        //  Catch the webhook instead of sending it again
        Http::fake(['*' => Http::response([], 204)]);

        //  Log the same record with the context a web request adds
        (new DiscordWebhookHandler('https://discord.test/hook', 'Nyumbani Bot', Level::Debug))->handle(new LogRecord(
            datetime: new DateTimeImmutable,
            channel: 'stack',
            level: $level,
            message: $message,
            context: ['name' => 'Jane Wanjiru', 'organization' => 'Acme Homes', 'request' => ['invoice' => 'INV-1']],
        ));

        //  Read the embed from the request that was caught
        $request = collect(Http::recorded())->map(fn (array $pair) => $pair[0])->first();
        $payload = json_decode(collect($request->data())->firstWhere('name', 'payload_json')['contents'], true);
        $embed = $payload['embeds'][0];

        return [
            $sent,
            Docs::row('Embed title', $embed['title']),
            Docs::row('Embed description', $embed['description']),
            Docs::row('Embed colour', '#'.str_pad(dechex($embed['color']), 6, '0', STR_PAD_LEFT)),
            ...collect($embed['fields'])->map(fn (array $field) => Docs::row('Embed field: '.$field['name'], $field['value']))->all(),
        ];
    }

    /**
     * Post a record to the app's discord log channel, and describe what happened
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function sendToDiscord(Level $level, string $message): array
    {
        //  Only an app with a webhook configured can send
        if (blank(config('logging.channels.discord.url'))) {
            return Docs::row('Sent to Discord', 'No: set DISCORD_WEBHOOK_URL and add the discord channel to config/logging.php', false);
        }

        //  Send it through the channel, with its throttle and truncation
        try {
            Log::channel('discord')->log(strtolower($level->getName()), $message, ['source' => 'docs playground']);
        } catch (Throwable $exception) {
            return Docs::row('Sent to Discord', 'No: '.$exception->getMessage(), false);
        }

        return Docs::row('Sent to Discord', 'Yes, look in your channel (levels below DISCORD_LOG_LEVEL are skipped)', true);
    }

    /**
     * Determine whether a value passes a validation rule
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function passes(ValidationRule $rule, string $value): bool
    {
        //  Count the failures the rule reports
        $failures = 0;
        $rule->validate('value', $value, function () use (&$failures) {
            $failures++;
        });

        return $failures === 0;
    }
}
