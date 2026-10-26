@extends('docs::layout')

@section('page', 'Anatomy of a feature')
@section('lead', 'One feature, a customer, from migration to view, with every Nyumbani convention applied and a pointer to the rule behind each choice.')

@section('content')
    <x-docs::section id="map" title="The layers">
        <p>A Nyumbani feature always has the same shape. Learn the order once and every app reads the same.</p>
        <x-docs::code lang="text" title="One request, top to bottom">
            routes/portal.php          explicit route, array callable, named
              |
            Form Request               validates, owns messages() and attributes()
              |
            Controller                 authorize, validate, call the model, log, flash
              |
            Model                      does the work: relationships, casts, scopes
              |
            Migration / Enum / Seeder  the data underneath
              |
            Blade view                 plain English, directives, components
        </x-docs::code>
        <p>The rules for each layer live on <a href="{{ $docs->url('http-layer') }}">HTTP layer</a>, <a href="{{ $docs->url('data-layer') }}">Models and data</a>, <a href="{{ $docs->url('app-code') }}">Application code</a> and <x-docs::package-link package="nyumbani-ui" page="view-layer">Views and scripts</x-docs::package-link>. This page shows them working together.</p>
    </x-docs::section>

    <x-docs::section id="data" title="1. The data">
        <x-docs::code title="database/migrations/create_customers_table.php">
            use App\Models\Organization;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            Schema::create('customers', function (Blueprint $table) {
                $table->id();
                $table->ulid()->unique();
                $table->foreignIdFor(Organization::class)->constrained();
                $table->string('name');
                $table->string('status');
                $table->timestamps();
            });
        </x-docs::code>
        <x-docs::code title="app/Enums/CustomerStatus.php">
            use Nyumbani\Essentials\Traits\HasEnumElements;

            enum CustomerStatus: string
            {
                use HasEnumElements;

                case ACTIVE = 'active';
                case ON_HOLD = 'on-hold';
            }
        </x-docs::code>
        <x-docs::code title="app/Models/Customer.php">
            use App\Enums\CustomerStatus;
            use Illuminate\Database\Eloquent\Model;
            use Nyumbani\Essentials\Casts\CleanName;
            use Nyumbani\Essentials\Traits\HasUlids;

            class Customer extends Model
            {
                use HasUlids;

                /**
                 * The attributes that can be mass assigned
                 */
                protected $fillable = ['name', 'status'];

                protected function casts(): array
                {
                    return ['name' => CleanName::class, 'status' => CustomerStatus::class];
                }
            }
        </x-docs::code>
        <ul>
            <li>The enum column is a plain <code>string</code>, cast to the enum in the model.</li>
            <li><code>$fillable</code> lists exactly what can be assigned. There is no <code>organization_id</code> in it: the tenant is never mass-assigned.</li>
            <li><code>HasUlids</code> is Essentials' own trait, so the public route key is the <code>ulid</code>.</li>
        </ul>
    </x-docs::section>

    <x-docs::section id="request" title="2. The Form Request">
        <x-docs::code title="app/Http/Requests/Admins/StoreCustomerRequest.php">
            use App\Enums\CustomerStatus;
            use Illuminate\Foundation\Http\FormRequest;
            use Illuminate\Validation\Rule;

            class StoreCustomerRequest extends FormRequest
            {
                public function rules(): array
                {
                    //  Define the name rule
                    $name = ['required', 'string', 'max:255'];

                    //  Define the status rule
                    $status = ['required', Rule::enum(CustomerStatus::class)];

                    return ['name' => $name, 'status' => $status];
                }

                public function attributes(): array
                {
                    return ['name' => 'customer name'];
                }
            }
        </x-docs::code>
        <p>Rules sit in named variables with a one-line comment, and friendly names live on the request, not in lang files. There is no <code>organization_id</code> rule: the tenant comes from the signed-in user.</p>
    </x-docs::section>

    <x-docs::section id="controller" title="3. The controller">
        <x-docs::code title="app/Http/Controllers/Admins/CustomerController.php">
            use App\Http\Requests\Admins\StoreCustomerRequest;
            use App\Models\Customer;
            use Illuminate\Http\RedirectResponse;
            use Illuminate\Support\Facades\Gate;

            class CustomerController
            {
                public function store(StoreCustomerRequest $request): RedirectResponse
                {
                    //  Authorize first
                    Gate::authorize('create', Customer::class);

                    //  Create through the tenant
                    $customer = user()->organization->customers()->create($request->validated());

                    //  Log the mutation
                    activity()->performedOn($customer)->event('customer-created')->log(trans('logs.customers.created'));

                    //  Tell the user
                    return back()->alert('success', trans('alerts.customers.created', ['name' => $customer->name]));
                }
            }
        </x-docs::code>
        <x-docs::table :columns="['Line', 'Rule']" :rows="[
            ['Gate::authorize(...) first', 'Authorization is the first line of every action, through a policy.'],
            ['user()->organization->customers()->create(...)', 'Records are created through the tenant\'s relation, never by merging an organization id.'],
            ['activity()->...->log(...)', 'Every mutation is logged with a kebab-case event name.'],
            ['back()->alert(...)', 'Feedback is a toast whose text is a lang key, never a hard-coded sentence.'],
        ]" />
    </x-docs::section>

    <x-docs::section id="view" title="4. The view">
        <p>The Quick start page of the UI package builds this layer: a page that extends the portal layout, formatting directives for figures and dates, and a modal with an error bag. The only addition for this feature is a status badge from the enum:</p>
        <x-docs::code lang="blade">
            @verbatim
            <span class="rounded bg-gray-100 px-2 py-0.5 text-xs">{{ $customer->status->label() }}</span>
            @endverbatim
        </x-docs::code>
        <p>See <x-docs::package-link package="nyumbani-ui" page="quick-start">Quick start</x-docs::package-link>.</p>
    </x-docs::section>

    <x-docs::section id="test" title="5. A test that proves it">
        <x-docs::code title="tests/Feature/CustomerTest.php">
            use App\Models\Customer;
            use App\Models\User;

            /**
             * A customer is created inside the user's organization
             *
             * @author Jane Doe <jane@example.com>
             */
            it('creates a customer in the organization', function () {
                $user = User::factory()->create();

                $this->actingAs($user)
                    ->post(route('customers.store'), ['_error_bag' => 'new-customer', 'name' => 'jane  wanjiru', 'status' => 'active'])
                    ->assertSessionHasNoErrors('new-customer');

                expect(Customer::first())
                    ->name->toBe('Jane Wanjiru')
                    ->organization_id->toBe($user->organization_id);
            });
        </x-docs::code>
        <p>The test checks the visible outcomes: the cleaned name, the tenant, and no errors in the modal's bag.</p>
    </x-docs::section>
@endsection
