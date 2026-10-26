<?php

namespace Nyumbani\Essentials\Console\Support;

/**
 * The Nyumbani packages an app chooses to install next to Essentials, with what each one adds to the app
 *
 * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
 */
class OptionalPackages
{
    /**
     * The packages by name, with a label, whether it is a dev dependency, the config tags and files, the environment keys and the rule files each one brings.
     *
     * @var array<string, array{label: string, dev: bool, config: array<string, string>, env: list<string>, rules: list<string>}>
     */
    public const PACKAGES = [
        'nyumbani/daraja' => [
            'label' => 'Daraja: M-Pesa payments through Safaricom',
            'dev' => false,
            'config' => ['daraja-config' => 'daraja.php'],
            'env' => ['DARAJA_ENVIRONMENT', 'DARAJA_SHORT_CODE', 'DARAJA_CONSUMER_KEY', 'DARAJA_CONSUMER_SECRET', 'DARAJA_PASS_KEY', 'DARAJA_TILL_NUMBER'],
            'rules' => [],
        ],
        'nyumbani/messaging' => [
            'label' => 'Messaging: SMS sent through Nyumbani Connect',
            'dev' => false,
            'config' => ['messaging-config' => 'messaging.php'],
            'env' => ['NYUMBANI_CONNECT_BASE_URL', 'NYUMBANI_CONNECT_TOKEN', 'MESSAGING_SMS_ENABLED', 'MESSAGING_SMS_SENDER_ID'],
            'rules' => ['messaging.md'],
        ],
        'nyumbani/dt-tables' => [
            'label' => 'Data tables: searchable, sortable tables',
            'dev' => false,
            'config' => ['dt-tables-config' => 'dt-tables.php'],
            'env' => [],
            'rules' => ['data-tables.md'],
        ],
        'nyumbani/ui' => [
            'label' => 'UI: layouts, sign-in pages, the modal and the theme',
            'dev' => false,
            'config' => ['ui-config' => 'ui.php'],
            'env' => ['UI_DESCRIPTION', 'UI_THEME_COLOR'],
            'rules' => ['views.md', 'js.md', 'livewire.md'],
        ],
        'nyumbani/docs' => [
            'label' => 'Docs: local-only guides with examples that run (dev dependency)',
            'dev' => true,
            'config' => [],
            'env' => [],
            'rules' => [],
        ],
    ];

    /**
     * Get the packages among the given list that are dev dependencies, which go in require-dev.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public static function dev(array $packages): array
    {
        return array_values(array_filter($packages, fn (string $package) => self::PACKAGES[$package]['dev'] ?? false));
    }

    /**
     * Get the package names mapped to their labels, for a prompt.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public static function labels(): array
    {
        return collect(self::PACKAGES)->map(fn (array $package) => $package['label'])->all();
    }

    /**
     * Get the optional packages the app already requires itself, which a run with nobody to ask keeps managing.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public static function required(): array
    {
        return array_values(array_diff(array_keys(self::PACKAGES), (new DirectRequirements)->missing(array_keys(self::PACKAGES))));
    }
}
