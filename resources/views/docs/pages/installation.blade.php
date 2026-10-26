@extends('docs::layout')

@section('page', 'Installation')
@section('lead', 'Add Essentials to a Laravel app in four steps. Add the UI package when the app needs the Nyumbani look.')

@section('content')
    <x-docs::section id="before" title="Before you start">
        <x-docs::table :columns="['You need', 'Why']" :rows="[
            ['PHP 8.3 or newer, Laravel 12 or 13', 'What the package supports.'],
            ['A User model and a users table', 'Fortify signs these in. The model implements HasHomeRoute, see Authentication.'],
            ['An Organization model', 'Only if your app is multi-tenant. Some traits and the log context look for it.'],
        ]" />
        <x-docs::callout type="note" title="Essentials has no screens">
            Essentials is headless: helpers, services, traits, permissions and the sign-in backend. The layouts, sign-in pages, modal, error pages and theme are the separate <x-docs::package-link package="nyumbani-ui">UI package</x-docs::package-link>, which needs Livewire 4, Tailwind CSS 4 and Vite. Install it only when the app needs those.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="require" title="1. Require the package">
        <p>Add Essentials to the app with Composer. It brings Fortify, Spatie permissions, activity log and impersonation with it.</p>
        <x-docs::code lang="bash" title="Terminal">
            composer require nyumbani/essentials
        </x-docs::code>
        <p>Laravel discovers the service provider on its own, so there is nothing to register. The other Nyumbani packages are optional and the installer in step 2 asks which ones the app uses:</p>
        <x-docs::table :columns="['Package', 'Adds']" :rows="[
            ['nyumbani/ui', 'Layouts, sign-in and error pages, the modal, toasts and the theme.'],
            ['nyumbani/daraja', 'M-Pesa payments through Safaricom Daraja.'],
            ['nyumbani/messaging', 'SMS sent through Nyumbani Connect.'],
            ['nyumbani/dt-tables', 'Searchable, sortable data tables.'],
            ['nyumbani/docs', 'The guides you are reading, served only locally. Always a dev dependency.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="install" title="2. Run the installer">
        <p>One command sets the app up, and it is safe to run again whenever a package changes. If Laravel Boost is missing, it installs it first with <code>composer require laravel/boost --dev</code>.</p>
        <x-docs::code lang="bash" title="Terminal">
            php artisan nyumbani:install
        </x-docs::code>
        <p>On an interactive run it shows a checkbox, <strong>Which Nyumbani packages does this app use?</strong>, with the packages above, the ones the app already requires ticked. It requires the ones you pick in your own <code>composer.json</code>, because Boost reads skills only from packages the app lists itself. It then asks <strong>Run the migrations now?</strong>, defaulting to no. When you picked the UI package, it also runs the UI installer, which asks <strong>Set up a basic portal?</strong> (see the <x-docs::package-link package="nyumbani-ui" page="installation">UI installation</x-docs::package-link>).</p>
        <x-docs::table :columns="['Step', 'What it does', 'On a rerun']" :rows="[
            ['Dependencies', 'Installs laravel/boost as a dev dependency if it is missing, and requires the optional packages you chose in your composer.json.', 'Skips what is already there. A package the app already requires and you leave out is kept unless you confirm its removal (default no) or pass --remove, which runs composer remove, drops it and its skills from boost.json and leaves your config file for you to delete.'],
            ['Rules', 'Copies the Nyumbani rules into .ai/rules and rebuilds the index. The view, JavaScript and Livewire rules are copied only when you chose the UI package.', 'Replaces them with the newest, and removes rules the package no longer ships.'],
            ['Config files', 'Publishes config/essentials.php, permission.php and activitylog.php, plus the config of each package you chose.', 'Skips any that exist, so your edits stay.'],
            ['Migrations', 'Publishes one migration that creates the permission tables with the group column built in, and the activity log migration. An app that already has Spatie\'s permission migration without the column gets a small one that adds it.', 'Skips any the app already has.'],
            ['.env.example', 'Adds the keys Essentials and the packages you chose read, blank.', 'Adds only the keys that are missing. Your .env is never touched.'],
            ['Log channel', 'Adds the discord channel to config/logging.php, with the driver\'s defaults for level, throttle and message length. You still add discord to the log stack.', 'Skips it if the app has a discord channel, or if the file has no channels list.'],
            ['permissions.json', 'Creates a starter database/seeders/data/permissions.json.', 'Skips it if it exists.'],
            ['UI (only if chosen)', 'Runs php artisan ui:install, which sets up the Vite aliases, the theme and script imports, the data tables look and the optional portal. Pass --portal to answer its question.', 'See the UI installation.'],
            ['Migrations (optional)', 'Asks Run the migrations now? when the permission and activity tables are missing, defaulting to no. On yes it runs php artisan migrate.', 'Skips the question once the tables exist.'],
            ['Roles and permissions', 'Runs php artisan nyumbani:permissions, which syncs the roles and permissions from database/seeders/data/permissions.json. It needs the permission tables, so on a first install it says to run php artisan migrate and then run the installer again, unless you answered yes to the migrations question.', 'Runs every time the tables exist, which is safe: it only creates what is missing, gives new permissions to users who hold the role and deletes the ones removed from the file.'],
            ['Storage link', 'Links public/storage for uploads.', 'Skips it if the link exists.'],
            ['Pint', 'Runs vendor/bin/pint on the PHP files the installer published or edited, and only those, so your own code is never reformatted. Skipped when the app has no Pint.', 'Runs every time, and only touches files that exist.'],
            ['Boost', 'The first time, runs boost:install with its own questions. Adds Essentials and the packages you chose, and their skills, to boost.json, uses Claude Code as the agent when none is chosen, and refreshes the guidelines and skills.', 'Only refreshes.'],
        ]" />
        <p>With <code>--no-interaction</code> (what Composer runs) it never asks: Boost's first install turns guidelines, skills and MCP all on, and only the packages the app already requires are managed.</p>
    </x-docs::section>

    <x-docs::section id="user" title="3. Prepare the user model">
        <p>The sign-in redirect asks the user model where to go, and permissions need its trait. Add the contract and the traits to your existing model; keep the traits it already uses, such as <code>HasFactory</code> and <code>Notifiable</code>.</p>
        <x-docs::code title="app/Models/User.php">
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Lab404\Impersonate\Models\Impersonate;
            use Nyumbani\Essentials\Contracts\HasHomeRoute;
            use Nyumbani\Essentials\Traits\HasDefaultPermissions;

            class User extends Authenticatable implements HasHomeRoute
            {
                use HasDefaultPermissions, Impersonate;

                public function homeRoute(): string
                {
                    return route('dashboard');
                }
            }
        </x-docs::code>
        <p>The installer never edits application code, so this step is yours. The checklist below tells you when it is done.</p>
    </x-docs::section>

    <x-docs::section id="verify" title="4. Migrate, seed and check">
        <x-docs::code lang="bash" title="Terminal">
            php artisan migrate
            php artisan nyumbani:permissions
            php artisan nyumbani:install
        </x-docs::code>
        <p>There is no registration page, so seed a first user and give it a role from <code>permissions.json</code> (see <a href="{{ $docs->url('permissions') }}">Permissions</a>).</p>
        <x-docs::code title="database/seeders/DatabaseSeeder.php">
            use App\Models\User;

            User::factory()
                ->create(['name' => 'Admin', 'email' => 'admin@example.com'])
                ->assignDefaultPermissions('admin');
        </x-docs::code>
        <p>Run the installer once more: every item on its checklist that applies to the app should read <strong>done</strong>. With the UI package, open <code>/login</code> and sign in; without it, Fortify has no pages until your own views are bound (see <a href="{{ $docs->url('authentication') }}#views">Swapping the sign-in pages</a>).</p>
    </x-docs::section>

    <x-docs::section id="checklist" title="The checklist">
        <p>The installer never edits your application code. When it finishes it prints what it found and what is left for you:</p>
        <x-docs::code lang="text" title="Setup checklist">
            User model implements HasHomeRoute ................. done
            User model has roles and default permissions ....... to do
                Use Nyumbani\Essentials\Traits\HasDefaultPermissions on the user model.
            User model can be impersonated ..................... to do
                Use Lab404\Impersonate\Models\Impersonate on the user model.
            Migrations have run (permissions with group, activity_log) .. to do
                Run php artisan migrate.
            Laravel Boost is installed ......................... done
            Boost is set up with the Nyumbani packages ......... done
            discord log channel ................................ optional
            Daraja credentials set ............................. optional
            Nyumbani Connect settings set ...................... optional
        </x-docs::code>
        <p><strong>to do</strong> items are needed for Essentials to work. <strong>optional</strong> items matter only if you use that feature: the Discord log channel and, for the packages you chose, the Daraja credentials and the Nyumbani Connect settings. Fix an item and run the command again to see it turn to <strong>done</strong>. The command ends with an invitation that links to the docs of each package. Read <a href="{{ $docs->url('rules-and-skills') }}">Rules and skills</a> for what lands where.</p>
    </x-docs::section>

    <x-docs::section id="options" title="Command options">
        <p><code>php artisan nyumbani:install</code> takes four options of its own, next to Laravel's (<code>-n</code> / <code>--no-interaction</code> never asks, <code>-q</code>, <code>-v</code>, <code>--env</code>).</p>
        <x-docs::table :columns="['Option', 'What it does', 'Example']" :rows="[
            ['--portal', 'Passes --portal to the UI installer, which sets up the basic portal (dashboard, layout and sidebar) without asking. It does nothing when the UI package is not chosen.', 'php artisan nyumbani:install --portal'],
            ['--with=', 'Installs the named optional packages without showing the checkbox. Names are daraja, messaging, dt-tables, ui and docs (or the full nyumbani/daraja form), comma separated or repeated. Packages the app already requires stay chosen.', 'php artisan nyumbani:install --with=ui,dt-tables'],
            ['--without=', 'Leaves the named optional packages out without showing the checkbox. A left-out package gets no config file, .env.example keys, rule, checklist item or Boost entry. It wins over --with and over a package the app already requires, which is kept unless you also remove it.', 'php artisan nyumbani:install --without=messaging'],
            ['--remove', 'Runs composer remove for each optional package the app requires and you left out, without asking, then drops it and its skills from boost.json and finishes in a fresh process. Your own config file is left for you to delete. Without this option, an interactive run asks once per package (default no) and a run with nobody to ask removes nothing.', 'php artisan nyumbani:install --without=messaging --remove'],
        ]" />
        <p><strong>Which packages are chosen.</strong> With neither <code>--with</code> nor <code>--without</code>, an interactive run shows the checkbox with the packages the app already requires ticked. With <code>--no-interaction</code> it chooses the packages the app already requires and never installs a new one, which is why Composer's <code>post-update-cmd</code> is safe. Either flag skips the checkbox: the chosen packages are the ones the app already requires, plus <code>--with</code>, minus <code>--without</code>.</p>
        <x-docs::code lang="bash" title="Terminal">
            # Ask which packages to install (interactive)
            php artisan nyumbani:install

            # A headless app: Essentials and data tables only, no UI, SMS or payments
            php artisan nyumbani:install --with=dt-tables --without=ui,messaging,daraja

            # Stop using Messaging: confirm the removal, or pass --remove to skip the question
            php artisan nyumbani:install --without=messaging
            php artisan nyumbani:install --without=messaging --remove

            # Set everything up in a script, with no questions (docs goes in require-dev)
            php artisan nyumbani:install --no-interaction --portal --with=ui,daraja,messaging,dt-tables,docs
        </x-docs::code>
        <p>When the installer has to require or remove packages it finishes in a second process, because the classes of the new packages are not loaded in the first. That process receives the choice as flags, so it never asks again.</p>
        <p><code>php artisan nyumbani:permissions</code> has no options. It syncs the roles and permissions from <code>database/seeders/data/permissions.json</code>, and the installer runs it for you once the permission tables exist.</p>
    </x-docs::section>
@endsection
