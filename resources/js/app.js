/**
 * ============================================================
 * Email Validator Pro - Vue.js 3 Application
 * Enterprise Email Validation Platform Frontend
 * ============================================================
 */

const { createApp, ref, reactive, computed, onMounted, onUnmounted } = Vue;

// ============================================================
// QUICK VALIDATOR COMPONENT
// Single email validation with real-time results
// ============================================================
const QuickValidator = {
    name: 'QuickValidator',
    props: {
        apiKey: { type: String, default: '' },
    },

    template: `
        <div class="quick-validator">
            <!-- Input Form -->
            <div class="row g-2 mb-3">
                <div class="col-md-8">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-envelope" :class="statusIconClass"></i>
                        </span>
                        <input
                            type="email"
                            class="form-control"
                            v-model="email"
                            placeholder="Enter email address to validate..."
                            @keyup.enter="validate"
                            :disabled="loading"
                        />
                        <button
                            class="btn btn-primary px-4"
                            @click="validate"
                            :disabled="loading || !email"
                        >
                            <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                            <i v-else class="bi bi-search me-2"></i>
                            {{ loading ? 'Validating...' : 'Validate' }}
                        </button>
                    </div>
                    <small class="text-muted">1 credit per validation. Cached results are free.</small>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button
                        class="btn btn-outline-secondary"
                        @click="clearResult"
                        :disabled="!result"
                    >
                        <i class="bi bi-x-circle"></i> Clear
                    </button>
                    <button
                        class="btn btn-outline-info"
                        @click="copyResult"
                        :disabled="!result"
                    >
                        <i class="bi bi-clipboard"></i> Copy JSON
                    </button>
                </div>
            </div>

            <!-- Error Message -->
            <div v-if="error" class="alert alert-danger d-flex align-items-center gap-2 mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>{{ error }}</span>
            </div>

            <!-- Validation Result -->
            <div v-if="result" class="result-card animate__animated animate__fadeIn">
                <!-- Status Header -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-top"
                     :style="{ background: statusBg, color: statusColor }">
                    <div class="d-flex align-items-center gap-3">
                        <div class="status-icon-lg">
                            <i :class="statusIcon" style="font-size:2rem"></i>
                        </div>
                        <div>
                            <div style="font-size:1.1rem;font-weight:700">{{ result.email }}</div>
                            <div style="font-size:0.85rem;opacity:0.9">
                                Status: <strong>{{ result.status.toUpperCase() }}</strong>
                                &bull; Score: <strong>{{ result.score }}/100</strong>
                            </div>
                        </div>
                    </div>
                    <!-- Score Gauge -->
                    <div class="text-center">
                        <div style="font-size:2.5rem;font-weight:800;line-height:1">{{ result.score }}</div>
                        <div style="font-size:0.7rem;opacity:0.8">QUALITY SCORE</div>
                        <div class="mt-1">
                            <span class="badge bg-white" :style="{ color: statusColor }">
                                {{ deliverability }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Score Bar -->
                <div style="height:6px;background:#e2e8f0">
                    <div :style="{ width: result.score + '%', height: '100%', background: statusColor, transition: 'width 0.6s ease' }"></div>
                </div>

                <!-- Detail Grid -->
                <div class="p-3 border rounded-bottom bg-white">
                    <div class="row g-3">

                        <!-- DNS Checks -->
                        <div class="col-md-4">
                            <h6 class="small fw-semibold text-muted text-uppercase mb-2">DNS Checks</h6>
                            <check-item label="MX Record Found" :value="result.mx_found" />
                            <check-item label="SPF Record" :value="result.spf_record" />
                            <check-item label="DMARC Record" :value="result.dmarc_record" />
                            <div v-if="result.mx_record" class="mt-2">
                                <small class="text-muted">MX Host:</small>
                                <div class="font-monospace small">{{ result.mx_record }}</div>
                            </div>
                        </div>

                        <!-- Email Properties -->
                        <div class="col-md-4">
                            <h6 class="small fw-semibold text-muted text-uppercase mb-2">Email Properties</h6>
                            <check-item label="SMTP Valid" :value="result.smtp_check" />
                            <check-item label="Syntax Valid" :value="result.syntax_valid" />
                            <check-item label="Free Email" :value="result.free_email" :invert="true" />
                            <check-item label="Role Based" :value="result.role_based" :invert="true" />
                            <div v-if="result.mailbox_provider" class="mt-2">
                                <small class="text-muted">Provider:</small>
                                <span class="badge bg-secondary ms-1">{{ result.mailbox_provider }}</span>
                            </div>
                        </div>

                        <!-- Risk Flags -->
                        <div class="col-md-4">
                            <h6 class="small fw-semibold text-muted text-uppercase mb-2">Risk Flags</h6>
                            <flag-item label="Catch All" :value="result.catch_all" />
                            <flag-item label="Disposable" :value="result.disposable" />
                            <flag-item label="Spam Trap" :value="result.spam_trap" />
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted d-block">Risk Level</small>
                                <span class="badge mt-1"
                                      :class="riskBadgeClass">
                                    {{ riskLevel }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Cache Notice -->
                    <div v-if="result.from_cache" class="mt-2 pt-2 border-top small text-muted">
                        <i class="bi bi-lightning-charge text-warning"></i>
                        Result served from cache (no credit charged)
                        &bull; Response: {{ result.response_time_ms }}ms
                    </div>
                </div>
            </div>
        </div>
    `,

    setup(props) {
        const email   = ref('');
        const loading = ref(false);
        const result  = ref(null);
        const error   = ref(null);

        const statusConfig = {
            valid:        { bg: '#dcfce7', color: '#16a34a', icon: 'bi bi-check-circle-fill' },
            invalid:      { bg: '#fee2e2', color: '#dc2626', icon: 'bi bi-x-circle-fill' },
            risky:        { bg: '#fef3c7', color: '#d97706', icon: 'bi bi-exclamation-triangle-fill' },
            unknown:      { bg: '#f1f5f9', color: '#64748b', icon: 'bi bi-question-circle-fill' },
            catch_all:    { bg: '#e0f2fe', color: '#0284c7', icon: 'bi bi-funnel-fill' },
            disposable:   { bg: '#fce7f3', color: '#be185d', icon: 'bi bi-trash-fill' },
            spam_trap:    { bg: '#1e1b4b', color: '#e0e7ff', icon: 'bi bi-shield-exclamation' },
        };

        const currentConfig = computed(() => statusConfig[result.value?.status] || statusConfig.unknown);
        const statusBg      = computed(() => currentConfig.value.bg);
        const statusColor   = computed(() => currentConfig.value.color);
        const statusIcon    = computed(() => currentConfig.value.icon);
        const statusIconClass = computed(() => result.value ? `text-${result.value.status === 'valid' ? 'success' : result.value.status === 'invalid' ? 'danger' : 'warning'}` : '');

        const deliverability = computed(() => {
            if (! result.value) return '';
            const score = result.value.score;
            if (score >= 80) return 'Excellent Deliverability';
            if (score >= 60) return 'Good Deliverability';
            if (score >= 40) return 'Fair Deliverability';
            if (score >= 20) return 'Poor Deliverability';
            return 'Do Not Send';
        });

        const riskLevel = computed(() => result.value?.risk_level?.replace('_', ' ').toUpperCase() || '');
        const riskBadgeClass = computed(() => ({
            'bg-success': result.value?.risk_level === 'low',
            'bg-warning text-dark': result.value?.risk_level === 'medium',
            'bg-danger': result.value?.risk_level === 'high',
            'bg-dark': result.value?.risk_level === 'very_high',
        }));

        async function validate() {
            if (!email.value || loading.value) return;

            loading.value = true;
            error.value   = null;
            result.value  = null;

            try {
                const response = await fetch('/api/v1/validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': props.apiKey,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ email: email.value }),
                });

                const data = await response.json();

                if (data.success) {
                    result.value = data;
                } else {
                    error.value = data.error || 'Validation failed. Please try again.';
                }
            } catch (err) {
                error.value = 'Network error. Please check your connection.';
            } finally {
                loading.value = false;
            }
        }

        function clearResult() {
            result.value = null;
            error.value  = null;
            email.value  = '';
        }

        function copyResult() {
            navigator.clipboard.writeText(JSON.stringify(result.value, null, 2));
            alert('Result copied to clipboard!');
        }

        return {
            email, loading, result, error,
            statusBg, statusColor, statusIcon, statusIconClass,
            deliverability, riskLevel, riskBadgeClass,
            validate, clearResult, copyResult,
        };
    }
};

// ============================================================
// CHECK ITEM SUB-COMPONENT (for DNS/SMTP checks)
// ============================================================
const CheckItem = {
    name: 'CheckItem',
    props: {
        label:  String,
        value:  { type: [Boolean, null], default: null },
        invert: { type: Boolean, default: false },
    },
    template: `
        <div class="d-flex align-items-center justify-content-between mb-1">
            <small class="text-muted">{{ label }}</small>
            <span v-if="value === null" class="badge bg-secondary" style="font-size:.65rem">N/A</span>
            <span v-else-if="isPositive" class="badge bg-success" style="font-size:.65rem">
                <i class="bi bi-check"></i> Yes
            </span>
            <span v-else class="badge bg-danger" style="font-size:.65rem">
                <i class="bi bi-x"></i> No
            </span>
        </div>
    `,
    computed: {
        isPositive() {
            return this.invert ? !this.value : this.value;
        }
    }
};

// ============================================================
// FLAG ITEM SUB-COMPONENT (for risk flags - inverted logic)
// ============================================================
const FlagItem = {
    name: 'FlagItem',
    props: {
        label: String,
        value: { type: [Boolean, null], default: null },
    },
    template: `
        <div class="d-flex align-items-center justify-content-between mb-1">
            <small class="text-muted">{{ label }}</small>
            <span v-if="value" class="badge bg-danger" style="font-size:.65rem">
                <i class="bi bi-exclamation"></i> Yes
            </span>
            <span v-else class="badge bg-success" style="font-size:.65rem">
                <i class="bi bi-check"></i> No
            </span>
        </div>
    `,
};

// ============================================================
// BULK UPLOAD COMPONENT
// ============================================================
const BulkUploader = {
    name: 'BulkUploader',
    template: `
        <div class="bulk-uploader">
            <!-- Drop Zone -->
            <div
                class="upload-zone border-2 border-dashed rounded-3 p-5 text-center mb-3"
                :class="{ 'dragging': isDragging, 'border-primary': isDragging, 'border-secondary': !isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onFileDrop"
                @click="$refs.fileInput.click()"
                style="cursor:pointer;background:#f8fafc;transition:all 0.2s"
            >
                <input ref="fileInput" type="file" accept=".csv,.txt,.xlsx" class="d-none" @change="onFileSelect" />

                <div v-if="!selectedFile">
                    <i class="bi bi-cloud-upload" style="font-size:3rem;color:#6366f1"></i>
                    <h5 class="mt-2 mb-1">Drop your email list here</h5>
                    <p class="text-muted mb-0">or click to browse</p>
                    <small class="text-muted">Supports: CSV, XLSX, TXT &bull; Max 100MB &bull; Up to 10M emails</small>
                </div>

                <div v-else class="d-flex align-items-center justify-content-center gap-3">
                    <i class="bi bi-file-earmark-text text-success" style="font-size:2.5rem"></i>
                    <div class="text-start">
                        <div class="fw-semibold">{{ selectedFile.name }}</div>
                        <small class="text-muted">{{ formatBytes(selectedFile.size) }}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" @click.stop="clearFile">
                        <i class="bi bi-x"></i>
                    </button>
                </div>
            </div>

            <!-- Options -->
            <div v-if="selectedFile" class="card border mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Job Name</label>
                            <input type="text" class="form-control" v-model="jobName" placeholder="My Email List" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Email Column (CSV/XLSX)</label>
                            <input type="text" class="form-control" v-model="emailColumn" placeholder="email" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Options</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" v-model="smtpValidation" id="smtpCheck">
                                <label class="form-check-label small" for="smtpCheck">SMTP Validation</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" v-model="skipDuplicates" id="dupCheck">
                                <label class="form-check-label small" for="dupCheck">Skip Duplicates</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Button -->
            <div v-if="selectedFile" class="d-flex gap-2">
                <button class="btn btn-primary" @click="uploadFile" :disabled="uploading">
                    <span v-if="uploading" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-upload me-2"></i>
                    {{ uploading ? 'Uploading...' : 'Start Validation' }}
                </button>
                <button class="btn btn-outline-secondary" @click="clearFile" :disabled="uploading">Cancel</button>
            </div>

            <!-- Error -->
            <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>

            <!-- Success -->
            <div v-if="jobId" class="alert alert-success mt-3">
                <h6><i class="bi bi-check-circle me-2"></i>Job Created Successfully!</h6>
                <p class="mb-2">Job ID: <code>{{ jobId }}</code></p>
                <progress-tracker :job-id="jobId"></progress-tracker>
            </div>
        </div>
    `,

    setup() {
        const selectedFile   = ref(null);
        const isDragging     = ref(false);
        const uploading      = ref(false);
        const error          = ref(null);
        const jobId          = ref(null);
        const jobName        = ref('');
        const emailColumn    = ref('email');
        const smtpValidation = ref(true);
        const skipDuplicates = ref(true);

        function onFileDrop(event) {
            isDragging.value = false;
            const file = event.dataTransfer.files[0];
            if (file) setFile(file);
        }

        function onFileSelect(event) {
            const file = event.target.files[0];
            if (file) setFile(file);
        }

        function setFile(file) {
            const allowedTypes = ['text/csv', 'text/plain', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
            if (! allowedTypes.includes(file.type) && ! file.name.match(/\.(csv|txt|xlsx)$/i)) {
                error.value = 'Invalid file type. Please upload CSV, TXT, or XLSX files only.';
                return;
            }

            if (file.size > 100 * 1024 * 1024) { // 100MB
                error.value = 'File size exceeds 100MB limit.';
                return;
            }

            selectedFile.value = file;
            jobName.value      = file.name.replace(/\.[^.]+$/, '');
            error.value        = null;
        }

        function clearFile() {
            selectedFile.value = null;
            jobId.value        = null;
            error.value        = null;
        }

        async function uploadFile() {
            if (!selectedFile.value || uploading.value) return;

            uploading.value = true;
            error.value     = null;

            const formData = new FormData();
            formData.append('file', selectedFile.value);
            formData.append('name', jobName.value);
            formData.append('email_column', emailColumn.value);
            formData.append('smtp_validation', smtpValidation.value ? '1' : '0');
            formData.append('skip_duplicates', skipDuplicates.value ? '1' : '0');

            try {
                const response = await fetch('/api/v1/bulk/upload', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    jobId.value      = data.job_id;
                    selectedFile.value = null;
                } else {
                    error.value = data.error || 'Upload failed.';
                }
            } catch (err) {
                error.value = 'Network error during upload. Please try again.';
            } finally {
                uploading.value = false;
            }
        }

        function formatBytes(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        return {
            selectedFile, isDragging, uploading, error, jobId,
            jobName, emailColumn, smtpValidation, skipDuplicates,
            onFileDrop, onFileSelect, clearFile, uploadFile, formatBytes,
        };
    }
};

// ============================================================
// PROGRESS TRACKER COMPONENT
// Real-time bulk job progress tracking
// ============================================================
const ProgressTracker = {
    name: 'ProgressTracker',
    props: { jobId: String },

    template: `
        <div class="progress-tracker">
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ statusText }}</span>
                <span>{{ job?.processed_emails?.toLocaleString() || 0 }} / {{ job?.total_emails?.toLocaleString() || 0 }}</span>
            </div>
            <div class="progress mb-2" style="height:12px;border-radius:8px">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     :style="{ width: progress + '%' }"
                     :class="progressClass">
                    {{ progress }}%
                </div>
            </div>
            <div v-if="job" class="row g-2 text-center small">
                <div class="col-3">
                    <div class="text-success fw-bold">{{ job.valid_emails?.toLocaleString() || 0 }}</div>
                    <div class="text-muted">Valid</div>
                </div>
                <div class="col-3">
                    <div class="text-danger fw-bold">{{ job.invalid_emails?.toLocaleString() || 0 }}</div>
                    <div class="text-muted">Invalid</div>
                </div>
                <div class="col-3">
                    <div class="text-warning fw-bold">{{ job.risky_emails?.toLocaleString() || 0 }}</div>
                    <div class="text-muted">Risky</div>
                </div>
                <div class="col-3">
                    <div class="text-secondary fw-bold">{{ job.processing_speed || 0 }}</div>
                    <div class="text-muted">emails/sec</div>
                </div>
            </div>
            <div v-if="job?.status === 'completed'" class="mt-2">
                <a :href="downloadUrl" class="btn btn-success btn-sm">
                    <i class="bi bi-download me-1"></i> Download Results
                </a>
            </div>
        </div>
    `,

    setup(props) {
        const job       = ref(null);
        let   interval  = null;

        const progress = computed(() => job.value?.progress || 0);
        const statusText = computed(() => {
            const s = job.value?.status;
            if (s === 'processing') return `Processing... ${job.value.estimated_seconds ? `~${Math.ceil(job.value.estimated_seconds/60)} min remaining` : ''}`;
            if (s === 'completed') return '✅ Completed!';
            if (s === 'failed') return '❌ Failed';
            return 'Waiting...';
        });

        const progressClass = computed(() => ({
            'bg-success': job.value?.status === 'completed',
            'bg-danger':  job.value?.status === 'failed',
            'bg-primary': job.value?.status === 'processing',
        }));

        const downloadUrl = computed(() =>
            job.value?.status === 'completed'
                ? `/api/v1/bulk/jobs/${props.jobId}/download?token=${job.value?.download_token}`
                : '#'
        );

        async function pollStatus() {
            try {
                const response = await fetch(`/api/v1/bulk/jobs/${props.jobId}`);
                const data     = await response.json();
                if (data.success) {
                    job.value = data.job;
                    if (['completed', 'failed', 'cancelled'].includes(data.job.status)) {
                        clearInterval(interval);
                    }
                }
            } catch (err) {
                console.error('Poll error:', err);
            }
        }

        onMounted(() => {
            pollStatus();
            interval = setInterval(pollStatus, 3000); // Poll every 3 seconds
        });

        onUnmounted(() => {
            if (interval) clearInterval(interval);
        });

        return { job, progress, statusText, progressClass, downloadUrl };
    }
};

// ============================================================
// BOOTSTRAP VUE APP
// ============================================================
const app = createApp({});

app.component('quick-validator', QuickValidator);
app.component('bulk-uploader', BulkUploader);
app.component('progress-tracker', ProgressTracker);
app.component('check-item', CheckItem);
app.component('flag-item', FlagItem);

app.mount('#app');
