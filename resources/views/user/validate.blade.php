@extends('layouts.app')

@section('title', 'Validate Email')
@section('page-title', 'Email Validator')

@section('content')
<div id="validatorApp">
    <div class="row g-4">
        <!-- Validator Form -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header py-3 px-4 d-flex align-items-center gap-2">
                    <i class="fas fa-magnifying-glass" style="color:#00d4ff;"></i>
                    <span class="fw-semibold">Single Email Validator</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex gap-2">
                        <input v-model="email" type="email" class="form-control form-control-lg"
                               placeholder="Enter email address to validate..."
                               @keydown.enter="validate" :disabled="loading">
                        <button @click="validate" class="btn btn-primary px-4" :disabled="loading || !email">
                            <span v-if="loading"><i class="fas fa-spinner fa-spin me-2"></i>Checking...</span>
                            <span v-else><i class="fas fa-search me-2"></i>Validate</span>
                        </button>
                    </div>

                    <!-- Result -->
                    <div v-if="result" class="mt-4">
                        <div class="p-4 rounded-3" :style="resultStyle">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <!-- Score Circle -->
                                <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold"
                                     style="width:72px;height:72px;font-size:1.5rem;flex-shrink:0;"
                                     :style="scoreStyle">
                                    @{{ result.score }}
                                </div>
                                <div>
                                    <div class="fw-bold fs-5" :style="{color: statusColor}">@{{ statusLabel }}</div>
                                    <div style="font-size:0.85rem;color:rgba(255,255,255,0.5);">@{{ result.email }}</div>
                                    <div class="mt-1">
                                        <span class="badge rounded-pill px-3" :style="badgeStyle">@{{ result.deliverability }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Check grid -->
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(0,0,0,0.2);">
                                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Syntax</div>
                                        <check-item :ok="result.syntax_valid" label="Format Valid"></check-item>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(0,0,0,0.2);">
                                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">DNS</div>
                                        <check-item :ok="result.mx_found" label="MX Records"></check-item>
                                        <check-item :ok="result.spf_record" label="SPF Record"></check-item>
                                        <check-item :ok="result.dmarc_record" label="DMARC Record"></check-item>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(0,0,0,0.2);">
                                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">SMTP</div>
                                        <check-item :ok="result.smtp_check" label="SMTP Verified"></check-item>
                                        <check-item :ok="!result.catch_all" label="Not Catch-All"></check-item>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded-2" style="background:rgba(0,0,0,0.2);">
                                        <div style="font-size:0.7rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Flags</div>
                                        <flag-item :flag="result.disposable" label="Disposable" :danger="true"></flag-item>
                                        <flag-item :flag="result.spam_trap" label="Spam Trap" :danger="true"></flag-item>
                                        <flag-item :flag="result.catch_all" label="Catch-All" :warn="true"></flag-item>
                                        <flag-item :flag="result.role_based" label="Role-Based" :warn="true"></flag-item>
                                        <flag-item :flag="result.free_email" label="Free Email" :danger="false"></flag-item>
                                    </div>
                                </div>
                            </div>

                            <!-- Mailbox Provider -->
                            <div v-if="result.mailbox_provider" class="mt-2 d-flex align-items-center gap-2" style="font-size:0.82rem;color:rgba(255,255,255,0.5);">
                                <i class="fas fa-inbox"></i> Provider: <strong style="color:rgba(255,255,255,0.8);">@{{ result.mailbox_provider }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="alert alert-danger mt-3">
                        <i class="fas fa-exclamation-circle me-2"></i>@{{ error }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-5">
            <div class="card mb-3">
                <div class="card-body p-4">
                    <h6 class="fw-semibold mb-3"><i class="fas fa-coins me-2" style="color:#ffd60a;"></i>Credit Balance</h6>
                    <div style="font-size:2rem;font-weight:800;background:linear-gradient(135deg,#00d4ff,#7b2ff7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                        {{ number_format(auth()->user()->credit_balance) }}
                    </div>
                    <div style="font-size:0.82rem;color:rgba(255,255,255,0.4);">credits remaining</div>
                    <div class="mt-2" style="font-size:0.8rem;color:rgba(255,255,255,0.4);">1 credit per validation</div>
                    <a href="{{ route('user.billing') }}" class="btn btn-sm btn-outline-light mt-3 w-100" style="font-size:0.8rem;">
                        <i class="fas fa-plus me-1"></i> Buy More Credits
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header py-3 px-4">
                    <span class="fw-semibold" style="font-size:0.9rem;">Score Guide</span>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <span style="font-size:0.875rem;">80–100</span>
                        <span class="badge" style="background:rgba(25,135,84,0.2);color:#6feaaa;border:1px solid rgba(25,135,84,0.3);">Valid / Safe to Send</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <span style="font-size:0.875rem;">60–79</span>
                        <span class="badge" style="background:rgba(13,202,240,0.2);color:#6ff0ff;border:1px solid rgba(13,202,240,0.3);">Probably Valid</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <span style="font-size:0.875rem;">40–59</span>
                        <span class="badge" style="background:rgba(255,193,7,0.2);color:#ffd60a;border:1px solid rgba(255,193,7,0.3);">Risky</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center py-2">
                        <span style="font-size:0.875rem;">0–39</span>
                        <span class="badge" style="background:rgba(220,53,69,0.2);color:#ff8a9a;border:1px solid rgba(220,53,69,0.3);">Invalid / Dangerous</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const { createApp, ref, computed } = Vue;

createApp({
    setup() {
        const email = ref('');
        const loading = ref(false);
        const result = ref(null);
        const error = ref(null);

        const statusLabel = computed(() => {
            if (!result.value) return '';
            const s = result.value.status;
            return s === 'valid' ? 'Valid Email' : s === 'invalid' ? 'Invalid Email' : s === 'risky' ? 'Risky Email' : 'Unknown';
        });

        const statusColor = computed(() => {
            const s = result.value?.status;
            return s === 'valid' ? '#6feaaa' : s === 'invalid' ? '#ff8a9a' : s === 'risky' ? '#ffd60a' : '#adb5bd';
        });

        const scoreStyle = computed(() => {
            const score = result.value?.score ?? 0;
            const bg = score >= 70 ? 'rgba(25,135,84,0.25)' : score >= 40 ? 'rgba(255,193,7,0.2)' : 'rgba(220,53,69,0.25)';
            const color = score >= 70 ? '#6feaaa' : score >= 40 ? '#ffd60a' : '#ff8a9a';
            return { background: bg, color };
        });

        const resultStyle = computed(() => {
            const s = result.value?.status;
            const colors = { valid: 'rgba(25,135,84,0.08)', invalid: 'rgba(220,53,69,0.08)', risky: 'rgba(255,193,7,0.05)' };
            const borders = { valid: 'rgba(25,135,84,0.25)', invalid: 'rgba(220,53,69,0.25)', risky: 'rgba(255,193,7,0.25)' };
            return { background: colors[s] || 'rgba(255,255,255,0.04)', border: `1px solid ${borders[s] || 'rgba(255,255,255,0.1)'}` };
        });

        const badgeStyle = computed(() => {
            const d = result.value?.deliverability;
            if (d === 'Deliverable') return { background: 'rgba(25,135,84,0.2)', color: '#6feaaa', border: '1px solid rgba(25,135,84,0.3)' };
            if (d === 'Undeliverable') return { background: 'rgba(220,53,69,0.2)', color: '#ff8a9a', border: '1px solid rgba(220,53,69,0.3)' };
            return { background: 'rgba(255,193,7,0.2)', color: '#ffd60a', border: '1px solid rgba(255,193,7,0.3)' };
        });

        async function validate() {
            if (!email.value || loading.value) return;
            loading.value = true; result.value = null; error.value = null;
            try {
                const res = await fetch('/api/v1/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-API-Key': '{{ $apiKey->key }}'
                    },
                    body: JSON.stringify({ email: email.value })
                });
                if (!res.ok && res.status !== 422) {
                    error.value = 'Server error (' + res.status + '). Please try again.';
                    return;
                }
                const data = await res.json();
                if (data.success === false) { error.value = data.error || 'Validation failed'; }
                else { result.value = data.data || data; }
            } catch (e) { error.value = 'Network error. Please try again. (' + e.message + ')'; }
            finally { loading.value = false; }
        }

        return { email, loading, result, error, statusLabel, statusColor, scoreStyle, resultStyle, badgeStyle, validate };
    },
    components: {
        CheckItem: {
            props: ['ok', 'label'],
            template: `<div style="display:flex;align-items:center;gap:6px;font-size:0.8rem;padding:2px 0;color:rgba(255,255,255,0.65);">
                <i :class="ok ? 'fas fa-circle-check' : 'fas fa-circle-xmark'" :style="{color: ok ? '#6feaaa' : '#ff8a9a', width:'14px'}"></i>
                @{{ label }}
            </div>`
        },
        FlagItem: {
            props: ['flag', 'label', 'danger', 'warn'],
            template: `<div style="display:flex;align-items:center;gap:6px;font-size:0.8rem;padding:2px 0;color:rgba(255,255,255,0.65);">
                <i :class="flag ? 'fas fa-triangle-exclamation' : 'fas fa-circle-check'"
                   :style="{color: flag ? (danger ? '#ff8a9a' : '#ffd60a') : '#6feaaa', width:'14px'}"></i>
                @{{ label }}
            </div>`
        }
    }
}).mount('#validatorApp');
</script>
@endpush
