<div class="panel-card p-6 max-w-4xl">

    <h3 class="text-sm font-semibold text-slate-800 mb-2">Mail & Bildirim Ayarları</h3>

    <p class="text-sm text-slate-600 mb-4">

        Bu ayarlar tüm sistem için geçerlidir. Override kapalıysa .env değerleri kullanılmaya devam eder.

    </p>



    <form method="POST" action="{{ route('super-admin.settings.mail.update') }}" class="space-y-4">

        @csrf



        <label class="flex items-center gap-2 text-sm text-slate-700">

            <input type="checkbox" name="override_enabled" value="1" class="rounded" @checked(old('override_enabled', $mailSettings['override_enabled'] ?? false))>

            SMTP ayarlarını sistem genelinde override et

        </label>



        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <div class="md:col-span-2">

                <label class="block text-sm font-medium text-slate-700">Gönderici Adı</label>

                <input type="text" name="from_name" value="{{ old('from_name', $mailSettings['from_name'] ?? '') }}" class="mt-1 w-full">

            </div>

            <div class="md:col-span-2">

                <label class="block text-sm font-medium text-slate-700">Gönderici E-posta</label>

                <input type="email" name="from_address" value="{{ old('from_address', $mailSettings['from_address'] ?? '') }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">SMTP Host</label>

                <input type="text" name="smtp_host" value="{{ old('smtp_host', $mailSettings['smtp_host'] ?? '') }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">SMTP Port</label>

                <input type="number" name="smtp_port" value="{{ old('smtp_port', $mailSettings['smtp_port'] ?? '') }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">SMTP Kullanıcı Adı</label>

                <input type="text" name="smtp_username" value="{{ old('smtp_username', $mailSettings['smtp_username'] ?? '') }}" class="mt-1 w-full">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">SMTP Şifre</label>

                <input type="password" name="smtp_password" value="" class="mt-1 w-full" placeholder="Boş bırakılırsa mevcut şifre korunur">

            </div>

            <div>

                <label class="block text-sm font-medium text-slate-700">Şifreleme</label>

                <select name="smtp_encryption" class="mt-1 w-full">

                    <option value="tls" @selected(old('smtp_encryption', $mailSettings['smtp_encryption'] ?? 'tls') === 'tls')>TLS</option>

                    <option value="ssl" @selected(old('smtp_encryption', $mailSettings['smtp_encryption'] ?? 'tls') === 'ssl')>SSL</option>

                    <option value="none" @selected(old('smtp_encryption', $mailSettings['smtp_encryption'] ?? 'tls') === 'none')>None</option>

                </select>

            </div>

        </div>



        <div class="border-t border-slate-200 pt-4 space-y-4">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div>

                    <label class="block text-sm font-medium text-slate-700">Quiet Hours Başlangıç</label>

                    <input type="time" name="default_quiet_hours_start" value="{{ old('default_quiet_hours_start', $mailSettings['default_quiet_hours_start'] ?? '22:00') }}" class="mt-1 w-full">

                </div>

                <div>

                    <label class="block text-sm font-medium text-slate-700">Quiet Hours Bitiş</label>

                    <input type="time" name="default_quiet_hours_end" value="{{ old('default_quiet_hours_end', $mailSettings['default_quiet_hours_end'] ?? '08:00') }}" class="mt-1 w-full">

                </div>

            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">

                <input type="checkbox" name="critical_email_default_enabled" value="1" class="rounded" @checked(old('critical_email_default_enabled', $mailSettings['critical_email_default_enabled'] ?? true))>

                Kritik bildirimlerde e-posta varsayılanı açık

            </label>

        </div>



        <div class="pt-2">

            <button type="submit" class="btn btn-solid-accent">Kaydet</button>

        </div>

    </form>



    <form method="POST" action="{{ route('super-admin.settings.mail.test') }}" class="mt-6 border-t border-slate-200 pt-4 space-y-3">

        @csrf

        <div>

            <label class="block text-sm font-medium text-slate-700">Test Mail Hedefi</label>

            <input type="email" name="to_email" class="mt-1 w-full" placeholder="ornek@domain.com">

        </div>

        <div class="flex items-center gap-3">

            <button type="submit" class="btn btn-outline-accent">Test mail gönder</button>

        </div>

    </form>

</div>

