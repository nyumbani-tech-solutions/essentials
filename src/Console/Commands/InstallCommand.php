<?php

namespace Nyumbani\Essentials\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Laravel\Boost\Console\InstallCommand as BoostInstallCommand;
use Laravel\Boost\Rules\RuleRepository;
use Nyumbani\Docs\DocsRegistry;
use Nyumbani\Essentials\Console\Support\DirectRequirements;
use Nyumbani\Essentials\Console\Support\InstallChecklist;
use Nyumbani\Essentials\Console\Support\OptionalPackages;
use ParseError;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Terminal;
use Symfony\Component\Process\Process as SymfonyProcess;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;

class InstallCommand extends Command
{
    /**
     * The prefix of every rule file the package owns inside the app's .ai/rules folder.
     */
    protected const RULE_PREFIX = 'nyumbani-';

    /**
     * The package whose guidelines and skills Boost always loads, before the optional packages the app chose.
     *
     * @var list<string>
     */
    protected const CORE_PACKAGES = ['nyumbani/essentials'];

    /**
     * The config tags every app gets, with the file each one creates in the app's config folder.
     *
     * @var array<string, string>
     */
    protected const CONFIG_TAGS = [
        'essentials-config' => 'essentials.php',
        'permission-config' => 'permission.php',
        'activitylog-config' => 'activitylog.php',
    ];

    /**
     * The migration tags to publish, with the name of the migration each one creates.
     *
     * @var array<string, string>
     */
    protected const MIGRATION_TAGS = [
        'activitylog-migrations' => 'create_activity_log_table',
    ];

    /**
     * The name of the migration Essentials ships to create the permission tables, with the group column built in, and the tag that publishes it.
     */
    protected const PERMISSIONS_MIGRATION = 'create_permission_tables';

    protected const PERMISSIONS_MIGRATION_TAG = 'essentials-permissions-migration';

    /**
     * The name of the migration that adds the group column to permission tables an app created earlier without it, and the tag that publishes it.
     */
    protected const GROUP_MIGRATION = 'update_permissions_table_add_group_column';

    protected const GROUP_MIGRATION_TAG = 'essentials-permissions-group-migration';

    /**
     * The environment keys every app reads, which .env.example should list, before the keys of the optional packages the app chose.
     *
     * @var list<string>
     */
    protected const ENV_KEYS = ['ESSENTIALS_CURRENCY', 'DISCORD_WEBHOOK_URL'];

    /**
     * The discord log channel added to config/logging.php, which leaves the level, throttle and message length to the driver's defaults.
     */
    protected const DISCORD_CHANNEL = <<<'PHP'
        'discord' => [
            'driver' => 'discord',
            'url' => env('DISCORD_WEBHOOK_URL'),
            'username' => env('APP_NAME', 'Nyumbani'),
        ],

PHP;

    /**
     * The environment variable that marks the second pass of the command, so a Boost that still cannot be loaded never loops.
     */
    protected const RESTARTED = 'NYUMBANI_INSTALL_RESTARTED';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nyumbani:install
        {--portal : Set up the basic portal of the UI package without asking}
        {--with=* : Optional packages to install without asking, such as daraja, messaging or dt-tables}
        {--without=* : Optional packages to leave out without asking}
        {--remove : Remove the optional packages left out that the app already requires, without asking}';

    /**
     * The optional Nyumbani packages the app uses, as full package names, chosen at the start of the run.
     *
     * @var list<string>
     */
    protected array $packages = [];

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up or refresh the Nyumbani rules, guidelines and skills in this app: safe to run again whenever a package changes';

    /**
     * Execute the console command.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    public function handle(): int
    {
        //  Greet the developer
        $this->banner('Nyumbani install');
        $this->line(' <fg=gray>Rules, guidelines, skills and the files your app needs, set up safely and re-runnable.</>');
        $this->newLine();

        //  Choose the optional packages this app uses
        $this->packages = $this->choosePackages();

        //  Remove the packages the app already requires and no longer wants, then let a fresh process carry on, since their classes are still loaded here
        $unwanted = $this->packagesToRemove();

        if ($unwanted !== []) {
            return $this->removePackages($unwanted) ? $this->restart() : self::FAILURE;
        }

        //  Install what is missing first, and let a fresh process finish, since the new classes are not loaded here
        if (! $this->isBoostInstalled() || $this->missingPackages() !== []) {
            return $this->installBoostAndContinue();
        }

        //  Copy the rules, replacing the ones the package manages
        $this->publishRules();

        //  Publish the config files and migrations of the packages, once
        $this->publishPackageFiles();

        //  Prepare the app's own files, adding only what is missing
        $this->addEnvKeys();

        //  Add the discord log channel
        $this->addLogChannel();

        //  Create the starter permissions file
        $this->createPermissionsFile();

        //  Set up the UI package, when the app uses it
        $this->installUi();

        //  Offer to run the migrations, so the roles and permissions can be synced in this run
        $this->offerMigrations();

        //  Sync the roles and permissions from the file, once the tables exist
        $this->syncPermissions();

        //  Link the public storage folder
        $this->linkStorage();

        //  Format the PHP files the installer published
        $this->formatPublishedFiles();

        //  Set up Boost the first time, then refresh its guidelines and skills
        $this->setUpBoost();

        //  Say what is still left to do by hand
        $this->showChecklist();

        //  Invite the developer to the docs
        $this->showDocsInvitation();

        //  Close with the next step
        $this->newLine();
        $this->banner('All set. Run php artisan migrate if the checklist asks for it, and run this command again whenever a package changes.');

        //  Success
        return self::SUCCESS;
    }

    /**
     * Publish the config files and the migrations of the packages Essentials builds on, each only when the app has none yet.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function publishPackageFiles(): void
    {
        //  Go through the config files, an existing one is never overwritten
        foreach ($this->configTags() as $tag => $file) {
            //  Keep the config the app already has
            if (File::exists(config_path($file))) {
                continue;
            }

            //  Publish the config file
            $this->callSilent('vendor:publish', ['--tag' => [$tag]]);

            //  Say what was published
            $this->done("Published config/{$file}.");

        }

        //  Go through the migrations, skipping any the app already has under that name, since each publish adds a new timestamped copy
        foreach (self::MIGRATION_TAGS as $tag => $name) {
            //  Keep the migration the app already has
            if (File::glob(database_path("migrations/*_{$name}.php")) !== []) {
                continue;
            }

            //  Publish the migration
            $this->callSilent('vendor:publish', ['--tag' => [$tag]]);

            //  Say what was published
            $this->done("Published the {$name} migration.");
        }

        //  The permission tables come from Essentials' own migration, which already has the group column, so one file does it all
        $this->publishPermissionsMigration();
    }

    /**
     * Give the app the permission tables with the group column: its own full migration for a new app, or the small one that adds the column to tables created without it.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function publishPermissionsMigration(): void
    {
        //  Find any permission tables migration the app already has
        $existing = File::glob(database_path('migrations/*_'.self::PERMISSIONS_MIGRATION.'.php'));

        //  A new app gets Essentials' migration, which creates the tables with the group column
        if ($existing === []) {
            $this->publishMigration(self::PERMISSIONS_MIGRATION_TAG, self::PERMISSIONS_MIGRATION);

            return;
        }

        //  A migration that already has the group column, or an upgrade for it, needs nothing more
        $hasGroup = collect($existing)->contains(fn (string $path) => str_contains(File::get($path), "'group'"));

        if ($hasGroup || File::glob(database_path('migrations/*_'.self::GROUP_MIGRATION.'.php')) !== []) {
            return;
        }

        //  Otherwise add the column to the tables the app created earlier
        $this->publishMigration(self::GROUP_MIGRATION_TAG, self::GROUP_MIGRATION);
    }

    /**
     * Publish one of Essentials' migrations by its tag, which gives it a timestamped name.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function publishMigration(string $tag, string $name): void
    {
        //  Publish the migration
        $this->callSilent('vendor:publish', ['--tag' => [$tag]]);

        //  Say what was published
        $this->done("Published the {$name} migration.");
    }

    /**
     * Append the package keys that .env.example lacks, with blank values. The real .env is never touched.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function addEnvKeys(): void
    {
        //  Find the example file
        $path = base_path('.env.example');

        //  Nothing to add to when the app has no .env.example
        if (! File::exists($path)) {
            return;
        }

        //  Read it and find the keys that are missing
        $content = File::get($path);
        $missing = collect($this->envKeys())->reject(fn (string $key) => preg_match('/^'.preg_quote($key, '/').'=/m', $content));

        //  Stop when every key is already listed
        if ($missing->isEmpty()) {
            return;
        }

        //  Add them as one block
        File::put($path, rtrim($content)."\n\n# Nyumbani packages\n".$missing->map(fn (string $key) => "{$key}=")->implode("\n")."\n");

        //  Say what was added
        $this->done("Added {$missing->count()} keys to .env.example.");
    }

    /**
     * Add the discord log channel to config/logging.php after the slack channel, unless the app has one already, and only when the file still parses as PHP afterwards.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function addLogChannel(): void
    {
        //  Nothing to add to when the app has no logging config
        $path = config_path('logging.php');

        if (! File::exists($path)) {
            return;
        }

        //  Keep the discord channel the app already has
        $content = File::get($path);

        if (preg_match('/[\'"]discord[\'"]\s*=>/', $content)) {
            return;
        }

        //  Put it after the slack channel, the other webhook channel, or before the stack channel when there is no slack, or first in the list when there is neither
        $updated = match (true) {
            (bool) preg_match("/^        'slack' => \[.*?^        \],\R/ms", $content) => preg_replace("/(^        'slack' => \[.*?^        \],\R)/ms", '$1'.PHP_EOL.rtrim(self::DISCORD_CHANNEL).PHP_EOL, $content, 1),
            str_contains($content, "        'stack' => [") => preg_replace("/^(        'stack' => \[)/m", self::DISCORD_CHANNEL.'$1', $content, 1),
            default => preg_replace("/('channels' => \[\R)/", '$1'.self::DISCORD_CHANNEL, $content, 1),
        };

        //  Leave the file alone when the channels list was not found
        if ($updated === null || $updated === $content) {
            return;
        }

        //  Never write a file that no longer parses
        try {
            token_get_all($updated, TOKEN_PARSE);
        } catch (ParseError) {
            return;
        }

        //  Save it
        File::put($path, $updated);

        //  Say what was added
        $this->done('Added the discord channel to config/logging.php. Add discord to LOG_STACK to use it.');
    }

    /**
     * Create a starter permissions.json, when the app has none.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function createPermissionsFile(): void
    {
        //  Find where the file belongs
        $path = database_path('seeders/data/permissions.json');

        //  Keep the app's own file
        if (File::exists($path)) {
            return;
        }

        //  Publish the starter, which has one role and its permissions
        $this->callSilent('vendor:publish', ['--tag' => ['essentials-permissions']]);

        //  Say what was created
        $this->done('Created database/seeders/data/permissions.json.');
    }

    /**
     * Hand the front end to the UI package's own installer when the app uses it, which sets up the theme, the scripts and the optional portal.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function installUi(): void
    {
        //  Leave the front end alone when the app does not use the UI package
        if (! in_array('nyumbani/ui', $this->packages)) {
            return;
        }

        //  Run its installer, which asks about the portal unless the flag answers it
        $this->call('ui:install', array_filter(['--portal' => $this->option('portal')]));
    }

    /**
     * Ask whether to run the migrations when the permission and activity tables are missing, which defaults to no and is never asked in a non-interactive run.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function offerMigrations(): void
    {
        //  Nothing to offer when the tables exist, or when nobody is there to answer
        if ((new InstallChecklist($this->packages))->hasTables() || ! $this->input->isInteractive()) {
            return;
        }

        //  Respect a no
        if (! confirm('Run the migrations now?', default: false)) {
            return;
        }

        //  Run them, and report a failure instead of stopping the install
        try {
            $this->call('migrate');
        } catch (Throwable $exception) {
            error("The migrations failed: {$exception->getMessage()}");
        }
    }

    /**
     * Sync the roles and permissions from permissions.json, which needs the permission tables the migrations create.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function syncPermissions(): void
    {
        //  Wait for the migrations, which this command never runs, and say how to carry on
        if (! (new InstallChecklist($this->packages))->hasTables()) {
            note('Roles and permissions are not synced yet: run php artisan migrate, then run this command again.');

            return;
        }

        //  Sync them, and report a file that cannot be read instead of stopping the install
        try {
            $this->call('nyumbani:permissions');
        } catch (Throwable $exception) {
            error("Roles and permissions were not synced: {$exception->getMessage()}");
        }
    }

    /**
     * Link the public storage folder, when a configured link is missing.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function linkStorage(): void
    {
        //  Find out whether any configured link is missing
        $missing = collect(config('filesystems.links', []))->keys()->contains(fn (string $link) => ! file_exists($link));

        //  Leave the links alone when they all exist
        if ($missing) {
            //  Create the links
            $this->callSilent('storage:link');

            //  Say what was linked
            $this->done('Linked the public storage folder.');
        }
    }

    /**
     * Print a message on a block in the Nyumbani primary colour, the way a prompt's intro and outro are drawn, which Laravel Prompts only draws in cyan.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function banner(string $message): void
    {
        $this->line("<bg=#d97706;fg=black;options=bold> {$message} </>");
    }

    /**
     * Print one finished step as a green tick and its message.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function done(string $message): void
    {
        $this->line("  <fg=green>✓</> {$message}");
    }

    /**
     * Run Pint on the PHP files the installer published or edited, and only those, so the app's own code is never reformatted. Skipped when the app has no Pint.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function formatPublishedFiles(): void
    {
        //  Nothing to format with when the app has no Pint
        if (! File::exists(base_path('vendor/bin/pint'))) {
            return;
        }

        //  Gather the files and folders the installer writes to, that exist
        $paths = collect([
            ...collect($this->configTags())->map(fn (string $file) => config_path($file)),
            config_path('logging.php'),
            ...File::glob(database_path('migrations/*_'.self::PERMISSIONS_MIGRATION.'.php')),
            ...File::glob(database_path('migrations/*_'.self::GROUP_MIGRATION.'.php')),
            ...collect(self::MIGRATION_TAGS)->flatMap(fn (string $name) => File::glob(database_path("migrations/*_{$name}.php"))),
        ])->filter(fn (string $path) => File::exists($path))->values();

        if ($paths->isEmpty()) {
            return;
        }

        //  Format them, and report a failure instead of stopping the install
        $result = spin(fn () => Process::path(base_path())->run([PHP_BINARY, 'vendor/bin/pint', ...$paths->all()]), 'Formatting the published files with Pint...');

        $result->successful()
            ? $this->done('Formatted the published files with Pint.')
            : note('Pint could not format the published files: run vendor/bin/pint by hand.');
    }

    /**
     * Print what the app still has to do by hand, found by looking, never by editing application code.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function showChecklist(): void
    {
        //  Find the checks
        $items = collect((new InstallChecklist($this->packages))->items());

        //  Count what is done
        $done = $items->where('state', 'pass')->count();
        $total = $items->count();

        //  Draw a bar for the progress, sized to a fixed width so it never wraps
        $filled = (int) round(($done / max($total, 1)) * 20);
        $bar = '<fg=green>'.str_repeat('━', $filled).'</><fg=gray>'.str_repeat('━', 20 - $filled).'</>';

        //  Print the heading
        $this->newLine();
        $this->line(" <options=bold>Setup checklist</>  {$bar}  <fg=gray>{$done} of {$total} done</>");
        $this->newLine();

        //  One line per check: a mark, then its label, in red while it is to do
        $width = max(40, (new Terminal)->getWidth() - 8);

        foreach ($items as $item) {
            [$mark, $color] = match ($item['state']) {
                'pass' => ['✓', 'green'],
                'optional' => ['○', 'yellow'],
                default => ['✗', 'red'],
            };

            //  A done check stays quiet
            if ($item['state'] === 'pass') {
                $this->line("  <fg=green>{$mark}</> <fg=gray>{$item['label']}</>");

                continue;
            }

            //  What is left shows its label and, beneath it, how to fix it
            $label = $item['state'] === 'optional' ? "<options=bold>{$item['label']}</> <fg=gray>(optional)</>" : "<fg=red;options=bold>{$item['label']}</>";
            $this->line("  <fg={$color};options=bold>{$mark}</> {$label}");
            $this->line('    <fg=#d97706>→</> <fg=gray>'.wordwrap($item['fix'], $width, "</>\n      <fg=gray>", false).'</>');
        }
    }

    /**
     * Invite the developer to the docs, which every Nyumbani package serves with examples that run.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function showDocsInvitation(): void
    {
        //  Leave the invitation out when the docs package is not installed
        if (! class_exists(DocsRegistry::class)) {
            return;
        }

        //  Find the docs the packages registered
        $registry = app(DocsRegistry::class);
        $essentials = $registry->find('nyumbani-essentials');

        //  Leave the invitation out when Essentials registered none
        if (! $essentials) {
            return;
        }

        //  Line up a link for the first page to read, then one for each package
        $links = collect([
            'Start here' => ($registry->find('nyumbani-ui') ?? $essentials)->url(in_array('nyumbani/ui', $this->packages) && $registry->find('nyumbani-ui') ? 'quick-start' : 'introduction'),
            'Essentials' => $essentials->url(),
            'UI' => in_array('nyumbani/ui', $this->packages) ? $registry->find('nyumbani-ui')?->url() : null,
            'Payments' => in_array('nyumbani/daraja', $this->packages) ? $registry->find('nyumbani-daraja')?->url() : null,
            'SMS' => in_array('nyumbani/messaging', $this->packages) ? $registry->find('nyumbani-messaging')?->url() : null,
            'Tables' => in_array('nyumbani/dt-tables', $this->packages) ? $registry->find('nyumbani-dt-tables')?->url() : null,
        ])->filter();

        //  Print the invitation
        $this->newLine();
        $this->line(' <fg=#d97706;options=bold>✦ Nyumbani Essentials is ready</>');
        $this->newLine();
        $this->line(' <fg=gray>Every feature has a page with examples you can run, not only read.</>');
        $this->newLine();

        //  One link per line, with the labels and addresses on opposite sides
        $links->each(fn (string $url, string $label) => $this->components->twoColumnDetail($label, $url));

        //  Say where the docs are served
        $this->newLine();
        $this->line(' <fg=gray>The docs are served only in the local environment.</>');
    }

    /**
     * Determine whether Laravel Boost is installed, which owns the guidelines, skills and the rules index.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function isBoostInstalled(): bool
    {
        return class_exists(BoostInstallCommand::class);
    }

    /**
     * Require what is missing, Boost and the Nyumbani packages Boost would otherwise ignore, then run the command again in a fresh process, which can load them.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function installBoostAndContinue(): int
    {
        //  A second pass that still finds something missing stops here instead of looping
        if (getenv(self::RESTARTED)) {
            //  Say how to do it by hand
            error('Something is still missing after installing it. Run composer require laravel/boost --dev'.($this->packages === [] ? '' : ' and composer require '.implode(' ', $this->packages)).', then run this command again.');

            //  Stop
            return self::FAILURE;
        }

        //  Install Boost when it is missing
        if (! $this->isBoostInstalled()) {
            //  Ask first when someone is there to answer
            if ($this->input->isInteractive() && ! confirm('Laravel Boost is missing. Install it now with composer require laravel/boost --dev?', default: true)) {
                //  Respect the answer
                error('Laravel Boost is needed for the rules, guidelines and skills. Run composer require laravel/boost --dev, then run this command again.');

                return self::FAILURE;
            }

            //  Stop when Composer cannot install it
            if (! $this->requireBoost()) {
                //  Say how to do it by hand
                error('Could not install laravel/boost. Run composer require laravel/boost --dev, then run this command again.');

                //  Stop
                return self::FAILURE;
            }
        }

        //  Require the packages the app chose, since Boost loads only the packages an app requires itself
        $missing = $this->missingPackages();

        if ($missing !== []) {
            //  Say why
            $dev = OptionalPackages::dev($missing);
            $production = array_values(array_diff($missing, $dev));
            $this->done('Installing '.collect([
                $production === [] ? null : implode(' and ', $production),
                $dev === [] ? null : implode(' and ', $dev).' as a dev dependency',
            ])->filter()->implode(', and ').', which the app chose and Boost reads only when the app requires it itself.');

            //  Stop when Composer cannot require them
            if (! $this->requirePackages($missing)) {
                //  Say how to do it by hand
                error('Could not require '.implode(' and ', $missing).'. Run composer require '.implode(' ', $missing).', then run this command again.');

                //  Stop
                return self::FAILURE;
            }
        }

        //  Finish the setup where the new packages are loaded
        return $this->restart();
    }

    /**
     * Get the chosen packages the app does not require itself yet.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function missingPackages(): array
    {
        //  A dev dependency in the require key counts as missing, so composer require --dev moves it
        return array_values(array_unique([
            ...(new DirectRequirements)->missing($this->packages),
            ...(new DirectRequirements)->misplaced(OptionalPackages::dev($this->packages)),
        ]));
    }

    /**
     * Choose the optional packages: from the flags when given, from a checkbox with the required ones ticked on an interactive run, and otherwise the ones the app already requires.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function choosePackages(): array
    {
        //  Read the flags, which accept a comma separated list too, and a short name such as messaging
        $with = $this->packageOption('with');
        $without = $this->packageOption('without');

        //  Ask only when nothing was decided by a flag and someone is there to answer
        if ($with === [] && $without === [] && $this->input->isInteractive()) {
            return multiselect(
                label: 'Which Nyumbani packages does this app use?',
                options: OptionalPackages::labels(),
                default: array_values(array_unique([...OptionalPackages::required(), 'nyumbani/docs'])),
                hint: 'Space selects, enter confirms. Essentials is always installed.',
            );
        }

        //  Keep what the app requires, add what was asked for and drop what was refused
        return collect([...OptionalPackages::required(), ...$with])
            ->unique()
            ->reject(fn (string $package) => in_array($package, $without))
            ->values()
            ->all();
    }

    /**
     * Get the optional packages the app requires but did not choose, which are removed only when the developer confirms each one or passes --remove, never on a non-interactive run without the flag.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function packagesToRemove(): array
    {
        //  The second pass never removes, so a removal that failed cannot loop
        if (getenv(self::RESTARTED)) {
            return [];
        }

        //  Find the packages the app requires itself and did not choose
        $unchosen = array_values(array_diff(OptionalPackages::required(), $this->packages));

        if ($unchosen === []) {
            return [];
        }

        //  The flag removes them all without asking
        if ($this->option('remove')) {
            return $unchosen;
        }

        //  Nobody is there to confirm, so keep them
        if (! $this->input->isInteractive()) {
            return [];
        }

        //  Ask about each one, defaulting to keeping it
        return array_values(array_filter($unchosen, fn (string $package) => confirm("Remove {$package} from this app? Code that uses it will stop working.", default: false)));
    }

    /**
     * Remove packages with Composer, then take them and their skills out of boost.json, and say what is left for the developer to delete.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function removePackages(array $packages): bool
    {
        //  Read the skills the packages ship before Composer deletes them
        $skills = collect($packages)
            ->map(fn (string $package) => base_path("vendor/{$package}/resources/boost/skills"))
            ->filter(fn (string $folder) => File::isDirectory($folder))
            ->flatMap(fn (string $folder) => File::directories($folder))
            ->map(fn (string $skill) => basename($skill));

        //  Run composer while a spinner shows
        $result = spin(
            fn () => Process::path(base_path())->forever()->run(['composer', 'remove', ...$packages, '--no-interaction']),
            'Removing '.implode(' and ', $packages).'...',
        );

        //  Stop when Composer cannot remove them
        if ($result->failed()) {
            note(trim($result->errorOutput() ?: $result->output()));
            error('Could not remove '.implode(' and ', $packages).'. Run composer remove '.implode(' ', $packages).' by hand.');

            return false;
        }

        //  Take them out of Boost's settings, so its agents stop reading their skills
        $this->forgetInBoost($packages, $skills->all());

        //  Say what was removed, and what only the developer can judge
        $this->done('Removed '.implode(' and ', $packages).'.');
        $files = collect($packages)->flatMap(fn (string $package) => array_values(OptionalPackages::PACKAGES[$package]['config']))->map(fn (string $file) => "config/{$file}");
        note('Your own files were left alone: '.($files->isEmpty() ? '' : "delete {$files->implode(', ')} if you no longer need them, and ").'check any code that used the packages.');

        return true;
    }

    /**
     * Remove packages and the skills they shipped from boost.json.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function forgetInBoost(array $packages, array $skills): void
    {
        //  Nothing to change before Boost has written its settings
        $path = base_path('boost.json');

        if (! File::exists($path)) {
            return;
        }

        //  Drop the packages and their skills
        $settings = json_decode(File::get($path), true) ?? [];
        $settings['packages'] = array_values(array_diff($settings['packages'] ?? [], $packages));
        $settings['skills'] = array_values(array_diff($settings['skills'] ?? [], $skills));

        //  Save the settings
        File::put($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * Read a package flag as full package names, from short or full names separated by commas or repeated flags.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function packageOption(string $name): array
    {
        return collect($this->option($name))
            ->flatMap(fn (string $value) => explode(',', $value))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->map(fn (string $value) => str_contains($value, '/') ? $value : "nyumbani/{$value}")
            ->filter(fn (string $package) => array_key_exists($package, OptionalPackages::PACKAGES))
            ->values()
            ->all();
    }

    /**
     * Get the config tags to publish: Essentials' own and those of the chosen packages.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function configTags(): array
    {
        return [
            ...self::CONFIG_TAGS,
            ...collect($this->packages)->flatMap(fn (string $package) => OptionalPackages::PACKAGES[$package]['config'])->all(),
        ];
    }

    /**
     * Get the environment keys .env.example should list: Essentials' own and those of the chosen packages.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function envKeys(): array
    {
        return [
            ...self::ENV_KEYS,
            ...collect($this->packages)->flatMap(fn (string $package) => OptionalPackages::PACKAGES[$package]['env'])->all(),
        ];
    }

    /**
     * Get the packages Boost should load: Essentials and the chosen packages.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function boostPackages(): array
    {
        return [...self::CORE_PACKAGES, ...array_diff($this->packages, OptionalPackages::dev($this->packages))];
    }

    /**
     * Run composer require for a list of packages behind a spinner, and show what Composer said if it fails.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function requirePackages(array $packages): bool
    {
        //  Dev dependencies go in require-dev, the others in require
        $dev = OptionalPackages::dev($packages);
        $production = array_values(array_diff($packages, $dev));
        $commands = array_filter([
            $production === [] ? null : ['composer', 'require', ...$production, '--no-interaction'],
            $dev === [] ? null : ['composer', 'require', '--dev', ...$dev, '--no-interaction'],
        ]);

        foreach ($commands as $command) {
            //  Run composer while a spinner shows
            $result = spin(fn () => Process::path(base_path())->forever()->run($command), 'Requiring '.implode(' and ', array_slice($command, $command[2] === '--dev' ? 3 : 2, -1)).'...');

            //  Show what Composer said when it failed, and stop
            if ($result->failed()) {
                note(trim($result->errorOutput() ?: $result->output()));

                return false;
            }
        }

        return true;
    }

    /**
     * Run composer require laravel/boost --dev behind a spinner, and show what Composer said if it fails.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function requireBoost(): bool
    {
        //  Run composer while a spinner shows
        $result = spin(
            fn () => Process::path(base_path())->forever()->run(['composer', 'require', 'laravel/boost', '--dev', '--no-interaction']),
            'Installing laravel/boost as a dev dependency...',
        );

        //  Show what Composer said when it failed
        if ($result->failed()) {
            note(trim($result->errorOutput() ?: $result->output()));
        }

        return $result->successful();
    }

    /**
     * Run this command again in a new process, interactive when this one is, and return its exit code.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function restart(): int
    {
        //  Keep asking questions only when this run does
        $interactive = $this->input->isInteractive();

        //  Run it in a new process, marked as the second pass, and show its output
        return Process::path(base_path())
            ->forever()
            ->env([self::RESTARTED => '1'])
            ->tty($interactive && SymfonyProcess::isTtySupported())
            ->run([PHP_BINARY, 'artisan', 'nyumbani:install', ...($interactive ? [] : ['--no-interaction']), ...($this->option('portal') ? ['--portal'] : []), ...$this->packageFlags()], fn (string $type, string $output) => $this->output->write($output))
            ->exitCode();
    }

    /**
     * Get the flags that carry the chosen packages into the second pass, so it never asks again.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function packageFlags(): array
    {
        //  Name every package, chosen or refused, so the flags always decide
        return [
            ...($this->packages === [] ? [] : ['--with='.implode(',', $this->packages)]),
            ...(array_diff(array_keys(OptionalPackages::PACKAGES), $this->packages) === [] ? [] : ['--without='.implode(',', array_diff(array_keys(OptionalPackages::PACKAGES), $this->packages))]),
        ];
    }

    /**
     * Copy the package rules into .ai/rules and rebuild the index.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function publishRules(): void
    {
        //  Get the destination folder
        $destination = base_path('.ai/rules');

        //  Make sure the folder exists
        File::ensureDirectoryExists($destination);

        //  Leave out the rules of optional packages the app did not choose
        $skipped = collect(OptionalPackages::PACKAGES)->except($this->packages)->flatMap(fn (array $package) => $package['rules']);
        $rules = collect(File::glob(essentials_path('resources/boost/rules/*.md')))
            ->reject(fn (string $path) => $skipped->contains(basename($path)));

        //  Collect the file names the package ships
        $shipped = $rules->map(fn (string $path) => self::RULE_PREFIX.basename($path));

        //  Remove the owned files the package no longer ships
        collect($this->installedRules())
            ->reject(fn (string $path) => $shipped->contains(basename($path)))
            ->each(fn (string $path) => File::delete($path));

        //  Copy every shipped rule, marking it as managed
        $rules->each(
            fn (string $path) => File::put(
                $destination.'/'.self::RULE_PREFIX.basename($path),
                $this->managedContent($path),
            )
        );

        //  Rebuild the rules index
        $this->rebuildIndex();

        //  Report the result
        $this->done("Published {$shipped->count()} rule files to .ai/rules.");
    }

    /**
     * Mark a rule as managed.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function managedContent(string $path): string
    {
        //  Read the rule
        $content = File::get($path);

        //  Define the notice
        $notice = "<!-- Managed by nyumbani/essentials. Do not edit: run php artisan nyumbani:install to refresh. -->\n\n";

        //  Place the notice after the frontmatter
        return (string) preg_replace('/^(---\R.*?\R---\R\R?)/s', '$1'.$notice, $content, 1);
    }

    /**
     * Return the paths of the rule files this package already put in the app.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function installedRules(): array
    {
        return File::glob(base_path('.ai/rules/'.self::RULE_PREFIX.'*.md')) ?: [];
    }

    /**
     * Install Boost the first time, when it has no settings yet, then load the Nyumbani packages and refresh its guidelines and skills.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function setUpBoost(): void
    {
        //  Install Boost only the first time, asking unless this run is non-interactive
        if (! File::exists(base_path('boost.json'))) {
            $this->input->isInteractive()
                ? $this->call('boost:install')
                : $this->runWithoutAsking('boost:install', ['--guidelines' => true, '--skills' => true, '--mcp' => true]);
        }

        //  Load the Nyumbani packages into Boost
        $this->enableBoostPackages();

        //  Refresh the guidelines and skills without asking, so it is safe in a composer script
        $this->runWithoutAsking('boost:update');
    }

    /**
     * Run another command with prompts switched off, which a nested call cannot do on its own.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function runWithoutAsking(string $command, array $options = []): int
    {
        //  Build the input and answer every prompt with its default
        $input = new ArrayInput($options);
        $input->setInteractive(false);

        return $this->getApplication()->find($command)->run($input, $this->output);
    }

    /**
     * Add Essentials and the chosen packages to the packages Boost loads from, which it keeps in boost.json.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function enableBoostPackages(): void
    {
        //  Nothing to enable until Boost has written its settings
        $path = base_path('boost.json');

        if (! File::exists($path)) {
            return;
        }

        //  Merge it into the packages Boost already loads
        $settings = json_decode(File::get($path), true) ?? [];
        $settings['packages'] = array_values(array_unique([...($settings['packages'] ?? []), ...$this->boostPackages()]));

        //  List the packages' skills too, since boost:update installs only the skills named here and finds new ones only by asking
        $settings['skills'] = array_values(array_unique([...($settings['skills'] ?? []), ...$this->packageSkills()]));

        //  A non-interactive first install picks no agent, and boost:update refuses to run without one, so default to Claude Code
        if (empty($settings['agents'])) {
            $settings['agents'] = ['claude_code'];
        }

        //  Save the settings
        File::put($path, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * Get the names of the skills the Nyumbani packages ship, read from their folders in vendor.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function packageSkills(): array
    {
        return collect($this->boostPackages())
            ->map(fn (string $package) => base_path("vendor/{$package}/resources/boost/skills"))
            ->filter(fn (string $folder) => File::isDirectory($folder))
            ->flatMap(fn (string $folder) => File::directories($folder))
            ->map(fn (string $skill) => basename($skill))
            ->values()
            ->all();
    }

    /**
     * Rebuild .ai/rules/index.md through Boost.
     *
     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
     */
    protected function rebuildIndex(): void
    {
        //  Let Boost rewrite the index
        app(RuleRepository::class)->writeIndex();
    }
}
