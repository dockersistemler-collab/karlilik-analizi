<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Profil Bilgileri
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Hesap bilgilerinizi ve e-posta adresinizi güncelleyin.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="'Ad Soyad'" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="'E-posta'" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        E-posta adresiniz doğrulanmamış.

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Doğrulama e-postasını yeniden göndermek için tıklayın.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            Doğrulama bağlantısı e-posta adresinize gönderildi.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="billing_name" :value="'Fatura Ünvanı'" />
            <x-text-input id="billing_name" name="billing_name" type="text" class="mt-1 block w-full" :value="old('billing_name', $user->billing_name)" autocomplete="organization" />
            <x-input-error class="mt-2" :messages="$errors->get('billing_name')" />
        </div>

        <div>
            <x-input-label for="billing_email" :value="'Fatura E-postası'" />
            <x-text-input id="billing_email" name="billing_email" type="email" class="mt-1 block w-full" :value="old('billing_email', $user->billing_email)" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('billing_email')" />
        </div>

        <div>
            <x-input-label for="billing_address" :value="'Fatura Adresi'" />
            <textarea id="billing_address" name="billing_address" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" rows="3">{{ old('billing_address', $user->billing_address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('billing_address')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Kaydet</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >Kaydedildi.</p>
            @endif
        </div>
    </form>
</section>
