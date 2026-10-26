@extends('docs::layout')

@section('page', 'HTTP layer')
@section('lead', 'Routes, controllers, Form Requests, resources, policies and language files.')

@section('content')
    <x-docs::section id="routes" title="Routes">
        <x-docs::rule title="Files split by audience">
            <p><code>web.php</code> holds the root redirect, <code>portal.php</code> the routes every signed-in user reaches, then one file per audience. Prefix, name and middleware, including the <code>role:</code> guard and the active-account check, are set once in <code>bootstrap/app.php</code>, never in the route files.</p>
        </x-docs::rule>
        <x-docs::rule title="Explicit verbs and array callables">
            <p>Define each route as <code>Route::get('x', [Controller::class, 'method'])-&gt;name('x')</code>. No closures (except the trivial root redirect), no <code>Route::resource</code> or <code>apiResource</code>, no group-level <code>-&gt;controller()</code>.</p>
            <x-docs::code lang="php" title="routes/portal.php">
                @verbatim
                use App\Http\Controllers\Admins\CustomerController;
                use Illuminate\Support\Facades\Route;

                Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
                @endverbatim
            </x-docs::code>

        </x-docs::rule>
        <x-docs::rule title="Fortify owns the auth routes">
            <p>Login, logout and password reset come from Fortify. Do not redefine them.</p>
        </x-docs::rule>
        <x-docs::rule title="Implicit binding">
            <p>Type-hint models in controller methods. No <code>Route::bind()</code> and no manual <code>findOrFail()</code>.</p>
        </x-docs::rule>
        <x-docs::rule title="Only routes that exist">
            <p>Comments use the <code>//  </code> format and each group's label matches its controller. Declare only routes whose controller method is implemented.</p>
        </x-docs::rule>
    </x-docs::section>

    <x-docs::section id="controllers" title="Controllers">
        <x-docs::rule title="Plain, audience-scoped controllers">
            <p>Controllers live in plural audience folders (<code>Admins/</code>, <code>SystemAdmins/</code>, <code>Customers/</code>, <code>Vendors/</code>), matching requests, services and view folders. They are plain classes grouped by resource, with custom action methods such as <code>activate</code> and <code>archive</code>, and <code>__invoke</code> for single-action pages.</p>
        </x-docs::rule>
        <x-docs::rule title="Authorize first, validate with a Form Request">
            <p>Call <code>Gate::authorize('ability', $model)</code> at the top of each action. Never use <code>$this-&gt;authorize()</code>, <code>-&gt;can()</code> or route-level <code>can:</code> middleware; Blade uses <code>@@can</code> only for conditional UI. Validate with a Form Request type-hinted in the signature, never <code>$request-&gt;validate()</code> or <code>Validator::make()</code>.</p>
        </x-docs::rule>
        <x-docs::rule title="Thin controllers">
            <p>Authorize, extract the validated data, then call an expressive method on the model, such as <code>$customer-&gt;createBid()</code>. There is no repository or query-object layer; query Eloquent directly.</p>
        </x-docs::rule>
        <x-docs::rule title="Read input the typed way">
            <p>Read validated data with <code>$request-&gt;safe()-&gt;field</code> or <code>-&gt;validated()</code>, never <code>input()</code>. Use <code>-&gt;boolean()</code>, <code>-&gt;date()</code> and <code>-&gt;enum()</code> for those three types, and plain access for strings and numbers, not <code>-&gt;string()</code> or <code>-&gt;integer()</code>.</p>
        </x-docs::rule>
        <x-docs::rule title="Bind the child, not the parent">
            <p>Never bind both a parent and its child model in one action. Bind the child and reach the parent through its relation, eager-loaded for authorization, and drop the unused parent segment from the URI. Two independent sibling models scoped together are fine.</p>
        </x-docs::rule>
        <x-docs::rule title="Create through the tenant">
            <p>Create tenant-owned records through the tenant's relation, <code>$tenant-&gt;customers()-&gt;create($validated)</code>, never by merging an <code>organization_id</code> into the data. The tenant id is never read from the request or a hidden field.</p>
        </x-docs::rule>
        <x-docs::rule title="Log every mutation">
            <p>Every create, update, activate or deactivate records <code>activity()-&gt;performedOn($model)-&gt;event('subject-verbed')-&gt;log('...')</code> with kebab-case event names.</p>
        </x-docs::rule>
        <x-docs::rule title="Flash through __()">
            <p>Redirect with <code>-&gt;alert('success'|'error'|'warning', __('...'))</code>, where the key is the English sentence or a nested alerts key such as <code>alerts.&lt;domain&gt;.&lt;action&gt;</code>. Messages are full sentences with <code>:placeholder</code> values. Never pass an untranslated string.</p>
        </x-docs::rule>
        <x-docs::rule title="Newest first">
            <p>Order plain listings with <code>-&gt;latest('id')</code>, qualified with the table when joined, not <code>latest()</code>. Skip it for manually ranked lists, grouped dashboards and inherently chronological views.</p>
        </x-docs::rule>
        <x-docs::rule title="Views get fluent objects">
            <p>Convert a service's arrays with <code>fluent($array)</code> before passing them to a view, so the view reads <code>$data-&gt;property</code>. Map nested arrays with <code>fluent($data)-&gt;map(fn ($item) =&gt; fluent($item))</code>.</p>
        </x-docs::rule>
    </x-docs::section>

    <x-docs::section id="requests" title="Form Requests">
        <x-docs::rule title="Audience folders with a shared base">
            <p>Requests live in plural audience folders. Rules shared by more than one audience go in a base request at the root of <code>app/Http/Requests</code>, which each audience class extends, merging extra rules through <code>parent::rules()</code>. Do not put shared rules on the model.</p>
        </x-docs::rule>
        <x-docs::rule title="One request for store and update">
            <p>Create one Form Request per resource and use it for both <code>store</code> and <code>update</code>, named after the model: <code>CustomerRequest</code>, not <code>StoreCustomerRequest</code> and <code>UpdateCustomerRequest</code>. Where the rules differ, branch on the route's model, which only an update has. Split into two requests only when the rules share almost nothing.</p>
            <x-docs::code lang="php" title="app/Http/Requests/Admins/CustomerRequest.php">
                use App\Models\Customer;
                use Illuminate\Foundation\Http\FormRequest;
                use Illuminate\Validation\Rule;

                class CustomerRequest extends FormRequest
                {
                    /**
                     * Get the rules for creating and for updating a customer
                     *
                     * @author Alvin G. Kaburu <geekaburu@nyumbanitech.co.ke>
                     */
                    public function rules(): array
                    {
                        //  Define the unique rule, ignoring the customer being updated
                        $unique = Rule::unique(Customer::class)->ignore($this->route('customer'));

                        return ['email' => ['required', 'email', $unique]];
                    }
                }
            </x-docs::code>
        </x-docs::rule>
        <x-docs::rule title="Messages and attributes live on the request">
            <p>Define <code>messages()</code> and <code>attributes()</code> on the Form Request, never in lang files. Every wildcard rule, such as <code>items.*.quantity</code>, needs a matching <code>attributes()</code> entry. The same goes for inline <code>validateWithBag()</code> calls, which take the attributes as the fourth argument.</p>
        </x-docs::rule>
        <x-docs::rule title="Named error bags">
            <p>A form that must keep its errors apart from other forms, such as a modal or one form per row, posts <code>@@bag($name)</code>. Nothing is needed on the request. Do not read a <code>_bag</code> field in <code>prepareForValidation()</code>, and add no trait or <code>failedValidation()</code> override. See Error bags.</p>
        </x-docs::rule>
        <x-docs::rule title="Model classes in exists and unique">
            <p>Pass the model class, <code>Rule::unique(User::class)</code>, never a table name. Assign each rule to a named variable with a one-line <code>//  Define the ...</code> comment, even when there is only one.</p>
            <x-docs::code lang="php" title="app/Http/Requests/Admins/StoreCustomerRequest.php">
                @verbatim
                use App\Models\Customer;
                use Illuminate\Validation\Rule;

                //  Define the unique email rule
                $email = Rule::unique(Customer::class);
                @endverbatim
            </x-docs::code>

        </x-docs::rule>
        <x-docs::rule title="Never validate the tenant id">
            <p>The tenant comes from the signed-in user in the controller, so a request has no <code>organization_id</code> rule or message. A foreign key to a tenant-owned record uses <code>Rule::exists(Model::class, 'id')</code> scoped with <code>-&gt;where('organization_id', $tenant-&gt;id)</code>, so a user cannot reference another tenant's record.</p>
        </x-docs::rule>
        <x-docs::rule title="Retrieve validated input">
            <p>Use <code>$request-&gt;safe()-&gt;field</code>, or <code>$this-&gt;field</code> inside the request's own methods. Use the typed getters only for boolean, date and enum input.</p>
        </x-docs::rule>
        <x-docs::rule title="No empty authorize()">
            <p>Do not define an <code>authorize()</code> that only returns <code>true</code>.</p>
        </x-docs::rule>
    </x-docs::section>

    <x-docs::section id="policies-resources" title="Policies, resources and language">
        <x-docs::rule title="Policies only">
            <p>All authorization lives in model policies, discovered by model. No <code>Gate::define</code>. Register <code>Gate::policy()</code> in <code>AuthServiceProvider</code> only for gates that are not model-backed. Check permissions with <code>$user-&gt;hasPermissionTo('verb-noun')</code>; never branch on roles in a policy. Users who are not system admins act only on their own tenant's records.</p>
        </x-docs::rule>
        <x-docs::rule title="API Resources for JSON">
            <p>Wrap models and collections returned from consumer-facing endpoints in a <code>JsonResource</code> or <code>toResourceCollection()</code>, not <code>response()-&gt;json()</code> or raw models. Resources return without the <code>data</code> wrapper. Keep <code>response()-&gt;json()</code> for acknowledging third-party webhooks and callbacks.</p>
        </x-docs::rule>
        <x-docs::rule title="Language keys">
            <p>Write user-facing and log messages through <code>__()</code> or <code>trans()</code>. The key is either the English sentence itself, translated in <code>lang/{code}.json</code> (<code>__('Sign In')</code>), or a short dotted key from a <code>lang/en/*.php</code> file (<code>trans('alerts.success.saved')</code>, <code>trans('logs.login')</code>). Dotted keys are not required: use whichever the file already uses, and do not rename existing keys. Blade copy is plain English unless the app serves more than one language, then it goes through <code>__()</code> with the English text as the key.</p>
            <x-docs::code lang="php" title="lang/en/alerts.php">
                @verbatim
                return [
                    'success' => [
                        'saved' => 'The customer :name was saved.',
                    ],
                ];
                @endverbatim
            </x-docs::code>

        </x-docs::rule>
    </x-docs::section>
@endsection
