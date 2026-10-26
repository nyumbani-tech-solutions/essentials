@extends('docs::layout')

@section('page', 'Traits')
@section('lead', 'Behaviour your models opt into. Essentials ships traits, not base models to extend.')

@section('content')
    <x-docs::section id="overview" title="The traits">
        <x-docs::table :columns="['Trait', 'Gives a model']" :rows="[
            ['HasUlids', 'A lower-case ULID stored in the ulid column on create and used as the route key, so URLs never expose the numeric id.'],
            ['HasMorphRouteKey', 'getMorphRouteKey(), formatted type:key, for polymorphic route binding.'],
            ['HasNumberIdentifiers', 'A year-scoped sequential number such as WOR-2026-0001.'],
            ['HasMoney', 'currencySymbol(), money() and relativeMoney() for amounts on the model.'],
            ['HasEnumElements', 'label(), lowerLabel() and random() for backed enums.'],
            ['HasUploads', 'File behaviour for the app Upload model: URL, size, extension and image check.'],
            ['HasProfileImage', 'A single replaceable profile image with an avatar fallback.'],
            ['HasKenyans', 'Realistic Kenyan names and safe fake phone numbers for factories.'],
            ['HasDefaultPermissions, HasRoleList', 'Roles and default permissions from permissions.json. Not Spatie\\Permission\\Traits\\HasPermissions, which has neither.'],
        ]" />
            <x-docs::playground demo="model-traits" title="Try the model and enum traits" :fields="[
            ['name' => 'amount', 'label' => 'An amount (try a negative one)', 'value' => '-250', 'placeholder' => 'e.g. 1500 or -250'],
            ['name' => 'status', 'label' => 'An enum case with HasEnumElements', 'type' => 'select', 'value' => 'partially-paid', 'options' => ['paid' => 'PAID', 'partially-paid' => 'PARTIALLY_PAID', 'on-hold' => 'ON_HOLD']],
        ]" />
    </x-docs::section>

    <x-docs::section id="ulids" title="Ulids">
        <p>The table keeps its auto-increment <code>id</code> as the internal key and gains a unique <code>ulid</code> column. The trait fills <code>ulid</code> when a row is created (unless you set one) and makes it the route key, so route model binding uses it.</p>
        <x-docs::code lang="php" title="database/migrations/create_customers_table.php">
            $table->id();
            $table->ulid()->unique();
        </x-docs::code>
        <x-docs::code>
            use Illuminate\Database\Eloquent\Model;
            use Nyumbani\Essentials\Traits\HasUlids;

            class Customer extends Model
            {
                use HasUlids;
            }

            $customer = Customer::create(['name' => 'Jane']);
            $customer->getRouteKey();   // 01j9x4k2m8qz... (never read $customer->ulid directly)
            route('customers.show', ['customer' => $customer]);   // /customers/01j9x4k2m8qz...
        </x-docs::code>
        <p><code>HasMorphRouteKey</code> adds <code>getMorphRouteKey()</code>, which joins the morph type and the route key as <code>customer:01j9x4k2m8qz...</code>, for a route that binds a model of one of several types.</p>
    </x-docs::section>

    <x-docs::section id="numbers" title="Number identifiers">
        <p>The number is built as prefix, year and a four digit sequence, and the sequence restarts each year. The prefix is the first three letters of the class name. Override <code>$numberPrefix</code> to choose your own. The table needs a <code>number</code> column, and the year comes from <code>created_at</code> (override <code>$numberDateColumn</code> to use another column). When the model has a <code>project_id</code> or <code>organization_id</code>, each of those counts its own sequence. A number you set yourself is kept.</p>
        <x-docs::code>
            use Illuminate\Database\Eloquent\Model;
            use Nyumbani\Essentials\Traits\HasNumberIdentifiers;

            class WorkOrder extends Model
            {
                use HasNumberIdentifiers;

                protected ?string $numberPrefix = 'WOR';
            }

            // WOR-2026-0001, WOR-2026-0002 ...
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="money" title="Money on a model">
        <x-docs::code>
            use Illuminate\Database\Eloquent\Model;
            use Nyumbani\Essentials\Traits\HasMoney;

            class Invoice extends Model
            {
                use HasMoney;
            }

            $invoice->money($invoice->total);                   // KES 1,500.00
            $invoice->money($invoice->total, currency: false);  // 1,500.00
            $invoice->money(-250);                              // KES 250.00 (absolute by default)
            $invoice->relativeMoney(-250);                      // KES -250.00 (keeps the sign)
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="enums" title="Enum elements">
        <x-docs::code>
            use Nyumbani\Essentials\Traits\HasEnumElements;

            enum InvoiceStatus: string
            {
                use HasEnumElements;

                case PAID = 'paid';
                case PARTIALLY_PAID = 'partially-paid';
            }

            InvoiceStatus::PARTIALLY_PAID->label();       // Partially Paid
            InvoiceStatus::PARTIALLY_PAID->lowerLabel();  // partially_paid
            InvoiceStatus::random();                      // a random case, for factories
        </x-docs::code>
        <x-docs::callout type="warning" title="Labels come from the case name">
            <code>label()</code> reads the case name, not the value, and lower-cases it before turning underscores into spaces. A case called <code>PartiallyPaid</code> would read <code>Partiallypaid</code>, so name cases in <code>UPPER_SNAKE_CASE</code>.
        </x-docs::callout>
    </x-docs::section>

    <x-docs::section id="uploads" title="Uploads and profile images">
        <p>The app owns its <code>Upload</code> model and its <code>UploadType</code> enum. <code>HasUploads</code> adds the file behaviour, and <code>HasProfileImage</code> reads the newest upload typed <code>profile-image</code>, falling back to a generated avatar from ui-avatars.com.</p>
        <x-docs::table :columns="['The Upload table needs', 'Because']" :rows="[
            ['path', 'The file\'s location on its disk.'],
            ['disk', 'Cast to UploadDisk (public or local). It defaults to public when saved. Only public uploads get a url.'],
            ['size', 'Filled on save from the stored file.'],
            ['type', 'profile-image for a profile image.'],
            ['owner_type, owner_id', 'The morph columns HasProfileImage queries, so the owner relation is named owner.'],
        ]" />
        <p><code>HasUploads</code> gives each upload <code>url</code>, <code>formatted_size</code>, <code>extension</code> and <code>isImage()</code>, and deletes the file from disk when the row is deleted.</p>
        <x-docs::code>
            use Illuminate\Database\Eloquent\Model;
            use Illuminate\Foundation\Auth\User as Authenticatable;
            use Illuminate\Database\Eloquent\Relations\MorphMany;
            use Nyumbani\Essentials\Traits\HasProfileImage;
            use Nyumbani\Essentials\Traits\HasUlids;
            use Nyumbani\Essentials\Traits\HasUploads;

            class Upload extends Model
            {
                use HasUlids, HasUploads;
            }

            class User extends Authenticatable
            {
                use HasProfileImage;

                public function uploads(): MorphMany
                {
                    return $this->morphMany(Upload::class, 'owner');
                }
            }

            $user->hasProfileImage();   // true once a profile-image upload exists
            $user->avatar;              // its url, or a generated avatar
        </x-docs::code>
        <p>The <x-docs::package-link package="nyumbani-ui" page="theme" anchor="file-input">file input component</x-docs::package-link> previews and replaces an upload in a form.</p>
    </x-docs::section>

    <x-docs::section id="kenyans" title="Kenyan names and numbers for factories">
        <x-docs::code>
            use Illuminate\Database\Eloquent\Factories\Factory;
            use Nyumbani\Essentials\Traits\HasKenyans;

            class CustomerFactory extends Factory
            {
                use HasKenyans;

                public function definition(): array
                {
                    return [
                        'name' => $this->generateKenyanName(),                 // Wanjiru Otieno
                        'phone_number' => $this->generateKenyanPhoneNumber(),  // 25471234567890
                    ];
                }
            }
        </x-docs::code>
        <p>The generated number is one digit too long on purpose, so a seeded or test record can never reach a real person by SMS. It also means it fails <code>KenyanNumberValid</code>.</p>
            <x-docs::playground demo="kenyans" title="Generate a name and number" :fields="[]" />
    </x-docs::section>
@endsection
