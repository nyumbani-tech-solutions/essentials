@extends('docs::layout')

@section('page', 'Validation')
@section('lead', 'Rules for Kenyan numbers and a cast that cleans names.')

@section('content')
    <x-docs::section id="form-requests" title="Use Form Requests">
        <p>Validate in a Form Request. Keep rules in named variables and give friendly names with <code>attributes()</code>.</p>
        <x-docs::code title="app/Http/Requests/StoreCustomerRequest.php">
            use Illuminate\Foundation\Http\FormRequest;
            use Nyumbani\Essentials\Rules\KenyanNumberValid;

            class StoreCustomerRequest extends FormRequest
            {
                public function rules(): array
                {
                    $name = ['required', 'string', 'max:255'];
                    $phone = ['required', new KenyanNumberValid];

                    return ['name' => $name, 'phone' => $phone];
                }

                public function attributes(): array
                {
                    return ['phone' => 'phone number'];
                }
            }
        </x-docs::code>
    </x-docs::section>

    <x-docs::section id="rules" title="Rules">
        <x-docs::table :columns="['Rule', 'Accepts']" :rows="[
            ['KenyanNumberValid', 'A Kenyan mobile number: an optional 0, 254 or +254, then 7xx or 10x/11x, then six more digits. So 0712345678, 254712345678, +254712345678, 712345678 and 0110123456 pass; 071234567 (one digit short) and 0212345678 (a landline) fail.'],
            ['PhoneNumberValid', 'An international number of 7 to 15 digits, with an optional leading +.'],
        ]" />
    </x-docs::section>

    <x-docs::playground demo="phone-rules" title="Try the phone rules" :fields="[
        ['name' => 'number', 'label' => 'A phone number', 'value' => '0712345678', 'placeholder' => 'e.g. 0712345678, +254712345678 or 071234567'],
    ]" />

    <x-docs::section id="clean-name" title="CleanName cast">
        <p>Collapses repeated spaces and title-cases a name on the way in. It never changes what is read back. It only cleans values that are non-empty strings.</p>
        <x-docs::code>
            use Illuminate\Database\Eloquent\Model;
            use Nyumbani\Essentials\Casts\CleanName;

            protected function casts(): array
            {
                return ['name' => CleanName::class];
            }

            //  "  jane    WANJIRU " is stored as "Jane Wanjiru"
            $customer->name = '  jane    WANJIRU ';
        </x-docs::code>
    </x-docs::section>

    <x-docs::playground demo="clean-name" title="Try the cast" :fields="[
        ['name' => 'name', 'label' => 'A name as typed', 'value' => '  jane    WANJIRU ', 'placeholder' => 'e.g.   jane    WANJIRU'],
    ]" />

    <x-docs::section id="error-bags" title="Error bags">
        <p>When several forms share a page, each needs its own errors. See <x-docs::package-link package="nyumbani-ui" page="error-bags">Error bags</x-docs::package-link> for the <code>@@bag</code> directive.</p>
    </x-docs::section>
@endsection
