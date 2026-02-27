@extends('layouts.admin')

@section('header')
    İletişim Detayı
@endsection

@section('content')
    @php
        $statusLabels = [
            'open' => 'Açık',
            'pending' => 'Beklemede',
            'answered' => 'Yanıtlandı',
            'closed' => 'Kapalı',
            'overdue' => 'Gecikmiş',
        ];
    @endphp

    <style>
        .cc-thread-hero {
            background:
                radial-gradient(120% 150% at 0% 0%, rgba(254, 226, 226, 0.7) 0%, transparent 50%),
                radial-gradient(90% 120% at 100% 0%, rgba(219, 234, 254, 0.7) 0%, transparent 55%),
                linear-gradient(140deg, #fff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1.2rem;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
        }

        .cc-card {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
        }

        .cc-bubble-in {
            background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
            color: #0f172a;
        }

        .cc-bubble-out {
            background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e3a8a;
        }

        .cc-compose-shell {
            border: 1px solid #dbe4ef;
            border-radius: 1rem;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            padding: .8rem;
        }

        .cc-compose-tools {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .6rem;
            align-items: center;
        }

        .cc-quick-templates {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-top: .6rem;
        }

        .cc-quick-btn {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #fff;
            color: #1e293b;
            font-size: .72rem;
            font-weight: 700;
            padding: .32rem .62rem;
            transition: .2s ease;
        }

        .cc-quick-btn:hover {
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .cc-reply-box {
            margin-top: .75rem;
            border: 1px solid #dbe4ef;
            border-radius: .95rem;
            overflow: hidden;
            background: #fff;
        }

        .cc-reply-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .55rem .75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            font-size: .78rem;
            font-weight: 700;
            color: #475569;
        }

        .cc-reply-box textarea {
            border: 0 !important;
            min-height: 170px;
            resize: vertical;
        }

        .cc-send-btn {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border: 1px solid #cbd5e1;
            border-radius: .8rem;
            background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
            color: #1e3a8a;
            font-weight: 800;
            padding: .52rem .85rem;
            transition: .2s ease;
        }

        .cc-send-btn:hover {
            border-color: #93c5fd;
            transform: translateY(-1px);
            box-shadow: 0 10px 18px rgba(59, 130, 246, 0.16);
        }

        .cc-info-card {
            border: 1px solid #dbe4ef;
            border-radius: .85rem;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
            padding: .75rem;
        }

        .cc-info-row {
            display: flex;
            align-items: center;
            gap: .55rem;
            color: #0f172a;
            font-size: .85rem;
            font-weight: 700;
        }

        .cc-info-row i {
            width: 1rem;
            text-align: center;
            color: #64748b;
        }
    </style>

    <div class="space-y-6">
        <section class="cc-thread-hero p-5 md:p-6">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5 text-sm">
                <div>
                    <div class="text-xs text-slate-500">Pazaryeri</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $thread->marketplace?->name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Mağaza</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $thread->marketplaceStore?->store_name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Müşteri</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ $thread->customer_name ?: '-' }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Kanal</div>
                    <div class="mt-1 font-semibold text-slate-900">{{ ucfirst($thread->channel) }}</div>
                </div>
                <div>
                    <div class="text-xs text-slate-500">Durum</div>
                    <div class="mt-1 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                        {{ $statusLabels[$thread->status] ?? $thread->status }}
                    </div>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <section class="cc-card p-4 xl:col-span-2">
                <div class="max-h-[460px] space-y-3 overflow-auto pr-1">
                    @forelse($thread->messages->sortBy('id') as $message)
                        <div class="flex {{ $message->direction === 'outbound' ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[86%] rounded-2xl px-3 py-2 text-sm {{ $message->direction === 'outbound' ? 'cc-bubble-out' : 'cc-bubble-in' }}">
                                <div class="leading-relaxed">{{ $message->body }}</div>
                                <div class="mt-1 text-[11px] opacity-80">
                                    {{ optional($message->created_at_external ?? $message->created_at)->format('d.m.Y H:i') }}
                                    @if($message->ai_suggested) · YZ öneri @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                            Bu iletişim için henüz mesaj yok.
                        </div>
                    @endforelse
                </div>

                <div class="mt-4 border-t border-slate-200 pt-4 space-y-3">
                    <div class="cc-compose-shell">
                        <div class="cc-compose-tools">
                            <select id="template-select" class="w-full">
                                <option value="">Hazır şablon seçin</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->body }}" data-template-id="{{ $template->id }}">{{ $template->title }}</option>
                                @endforeach
                            </select>
                            <button id="ai-suggest-btn" type="button" class="btn btn-outline inline-flex items-center gap-2"><i class="fa-solid fa-wand-magic-sparkles"></i><span>AI Öner</span></button>
                        </div>

                        @if($templates->isNotEmpty())
                            <div class="cc-quick-templates">
                                @foreach($templates->take(4) as $template)
                                    <button type="button" class="cc-quick-btn" data-quick-template="{{ e($template->body) }}">
                                        <i class="fa-regular fa-file-lines mr-1"></i>{{ $template->title }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div id="ai-info" class="hidden rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs text-sky-900 mt-3"></div>

                        <form method="POST" action="{{ route('portal.communication-center.thread.reply', $thread) }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="used_ai" id="used-ai" value="{{ old('used_ai', '0') }}">
                            <input type="hidden" name="ai_template_id" id="ai-template-id" value="{{ old('ai_template_id') }}">
                            <input type="hidden" name="ai_confidence" id="ai-confidence" value="{{ old('ai_confidence') }}">

                            <div class="cc-reply-box">
                                <div class="cc-reply-head">
                                    <span><i class="fa-regular fa-pen-to-square mr-1"></i>Yanıt Metni</span>
                                    <span id="reply-char-count">{{ mb_strlen((string) old('body', '')) }} karakter</span>
                                </div>
                                <textarea id="reply-body" name="body" rows="6" class="w-full" required>{{ old('body') }}</textarea>
                            </div>
                            <button class="cc-send-btn"><i class="fa-regular fa-paper-plane"></i><span>Gönder</span></button>
                        </form>
                    </div>
                </div>
            </section>

            <aside class="cc-card p-4 text-sm space-y-3">
                <h3 class="text-sm font-semibold text-slate-900">Ek Bilgiler</h3>
                <div class="cc-info-card space-y-2">
                    <div class="cc-info-row"><i class="fa-solid fa-box-open"></i><span>Ürün: {{ $thread->product_name ?: '-' }}</span></div>
                    <div class="cc-info-row"><i class="fa-solid fa-barcode"></i><span>Ürün Kodu: {{ $thread->product_sku ?: '-' }}</span></div>
                </div>
                <div class="cc-info-card space-y-2">
                    <div class="cc-info-row"><i class="fa-solid fa-receipt"></i><span>Sipariş: {{ $thread->external_order_id ?: '-' }}</span></div>
                    <div class="cc-info-row"><i class="fa-regular fa-comment-dots"></i><span>Konu: {{ $thread->subject ?: '-' }}</span></div>
                </div>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const templateSelect = document.getElementById('template-select');
        const replyBody = document.getElementById('reply-body');
        const aiInfo = document.getElementById('ai-info');
        const usedAiInput = document.getElementById('used-ai');
        const aiTemplateIdInput = document.getElementById('ai-template-id');
        const aiConfidenceInput = document.getElementById('ai-confidence');
        const replyCharCount = document.getElementById('reply-char-count');

        const updateCharCount = () => {
            if (!replyBody || !replyCharCount) return;
            replyCharCount.textContent = `${replyBody.value.length} karakter`;
        };

        templateSelect?.addEventListener('change', function () {
            if (this.value && replyBody) {
                replyBody.value = this.value;
                updateCharCount();
            }
            if (usedAiInput) usedAiInput.value = '0';
            if (aiTemplateIdInput) aiTemplateIdInput.value = '';
            if (aiConfidenceInput) aiConfidenceInput.value = '';
            if (aiInfo) aiInfo.classList.add('hidden');
        });

        document.getElementById('ai-suggest-btn')?.addEventListener('click', async function () {
            const url = "{{ route('portal.communication-center.thread.ai-suggest', $thread) }}";
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                }
            });

            if (!res.ok) {
                return;
            }

            const data = await res.json();

            if (replyBody && data.suggested_text) {
                replyBody.value = data.suggested_text;
                updateCharCount();
            }

            if (usedAiInput) usedAiInput.value = '1';
            if (aiTemplateIdInput) aiTemplateIdInput.value = data.template_id ?? '';
            if (aiConfidenceInput) aiConfidenceInput.value = data.confidence ?? '';

            if (templateSelect && data.template_id) {
                const option = Array.from(templateSelect.options)
                    .find((item) => String(item.dataset.templateId || '') === String(data.template_id));
                if (option) {
                    templateSelect.value = option.value;
                }
            }

            if (aiInfo) {
                const reasons = Array.isArray(data.reason)
                    ? data.reason.map((x) => String(x).replace('keyword:', '')).join(', ')
                    : '';
                const source = data.source ? ` | Kaynak: ${data.source}` : '';
                aiInfo.textContent = `AI Güven: ${data.confidence ?? 0}%${source}${reasons ? ` (${reasons})` : ''}`;
                aiInfo.classList.remove('hidden');
            }
        });

        document.querySelectorAll('[data-quick-template]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (!replyBody) return;
                replyBody.value = btn.getAttribute('data-quick-template') || '';
                updateCharCount();
                if (usedAiInput) usedAiInput.value = '0';
                if (aiTemplateIdInput) aiTemplateIdInput.value = '';
                if (aiConfidenceInput) aiConfidenceInput.value = '';
                if (aiInfo) aiInfo.classList.add('hidden');
            });
        });

        replyBody?.addEventListener('input', updateCharCount);
        updateCharCount();
    </script>
@endpush
