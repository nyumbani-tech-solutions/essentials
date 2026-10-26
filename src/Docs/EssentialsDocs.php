<?php

namespace Nyumbani\Essentials\Docs;

use Nyumbani\Docs\Docs;

/**
 * The docs of Essentials: its pages, grouped by the concept they explain, and the demos they run
 *
 * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
 */
class EssentialsDocs
{
    /**
     * Describe the docs of Essentials
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public static function describe(): Docs
    {
        $demos = new EssentialsDemos;

        return (new Docs('nyumbani-essentials', 'Essentials', [
            ['title' => 'Getting started', 'pages' => [
                'introduction' => 'Introduction',
                'installation' => 'Installation',
                'configuration' => 'Configuration',
            ]],
            ['title' => 'Foundation', 'pages' => [
                'defaults' => 'App defaults',
                'helpers' => 'Helpers',
                'period' => 'Period filter',
                'validation' => 'Validation',
                'javascript' => 'JavaScript',
                'logging' => 'Logging to Discord',
                'messaging' => 'Messaging',
                'daraja' => 'Daraja',
                'dt-tables' => 'Data tables',
            ]],
            ['title' => 'Users and models', 'pages' => [
                'authentication' => 'Authentication',
                'permissions' => 'Roles and permissions',
                'traits' => 'Traits',
            ]],
            ['title' => 'Conventions', 'pages' => [
                'rules-and-skills' => 'Rules and skills',
                'anatomy' => 'Anatomy of a feature',
                'code-style' => 'Code style',
                'app-code' => 'Application code',
                'http-layer' => 'HTTP layer',
                'data-layer' => 'Models and data',
            ]],
        ], 'essentials::docs.pages'))
            ->demo('period', $demos->period(...))
            ->demo('money', $demos->money(...))
            ->demo('dates', $demos->dates(...))
            ->demo('text', $demos->text(...))
            ->demo('phone-rules', $demos->phoneRules(...))
            ->demo('clean-name', $demos->cleanName(...))
            ->demo('kenyans', $demos->kenyans(...))
            ->demo('model-traits', $demos->modelTraits(...))
            ->demo('discord', $demos->discord(...));
    }
}
