<div align="center">

# ⚡ Power Email Validation
### Enterprise-Grade Email Validation Platform

**The most accurate, scalable, and developer-friendly email verification engine — built for businesses who demand zero bounce rates.**

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-7.2-DC382D?style=for-the-badge&logo=redis&logoColor=white)](https://redis.io)
[![RabbitMQ](https://img.shields.io/badge/RabbitMQ-3.12-FF6600?style=for-the-badge&logo=rabbitmq&logoColor=white)](https://rabbitmq.com)
[![Docker](https://img.shields.io/badge/Docker-24+-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com)
[![License](https://img.shields.io/badge/License-Proprietary-red?style=for-the-badge)](LICENSE)

---

[🚀 Quick Start](#-quick-start) • [📖 API Docs](#-api-reference) • [🏗 Architecture](#-system-architecture) • [⚙️ Configuration](#%EF%B8%8F-configuration) • [🐳 Deployment](#-docker-deployment)

</div>

---

## 🎯 What is Power Email Validation?

**Power Email Validation** is a production-grade, self-hosted email verification platform comparable to **ZeroBounce**, **NeverBounce**, and **MillionVerifier** — but with full source code ownership, unlimited scale, and zero per-email licensing fees.

Built on **Laravel 12** with a **7-layer validation pipeline**, it performs real SMTP conversations, DNS health checks, spam trap detection, disposable email filtering, and AI-powered scoring to give you the most accurate email deliverability data available.

---

## 💎 Unique Selling Propositions (USPs)

### 🔬 1. 7-Layer Deep Validation Engine
Unlike basic validators that only check syntax, Power Email Validation runs every email through **7 sequential validation layers** — each building on the last:

| Layer | Check | Speed |
|-------|-------|-------|
| 1️⃣ Syntax | RFC 5321/5322 compliance | ~1ms |
| 2️⃣ Disposable Detection | 100,000+ disposable domain database | ~2ms |
| 3️⃣ Spam Trap Detection | Honeypot + toxic domain matching | ~2ms |
| 4️⃣ DNS Validation | MX, A, SPF, DMARC record checks | ~50ms |
| 5️⃣ Mailbox Detection | Provider fingerprinting | ~5ms |
| 6️⃣ SMTP Validation | Real RCPT TO conversation | 1–10s |
| 7️⃣ AI Scoring Engine | 0–100 deliverability score | ~1ms |

### ⚡ 2. Blazing Performance at Scale
- **Cached results** returned in **< 5ms**
- **~100 emails/second** throughput with 4 SMTP workers
- **10 million emails** processable in a single bulk job
- Horizontal scaling: add workers → increase throughput **linearly**

### 🏢 3. True Enterprise Architecture
- **MySQL master-replica cluster** for read/write separation
- **RabbitMQ message broker** with dedicated queues per task type
- **Redis** for multi-database caching (cache, sessions, rate-limiting, queues)
- **NGINX load balancer** with SSL termination
- **Supervisor** process management for zero-downtime workers

### 🔒 4. Self-Hosted = 100% Data Ownership
- Your data **never leaves your infrastructure**
- No per-email pricing — pay only for your own servers
- White-label ready with full source code access
- GDPR compliant by design

### 🧠 5. Intelligent Scoring Engine (0–100)
Every email gets a **deliverability score** with a full point breakdown:
- `+35` — SMTP mailbox confirmed
- `+20` — Valid MX record found
- `+8` — DMARC policy present
- `+7` — SPF record present
- `+5` — Trusted provider (Gmail, Outlook, Yahoo)
- `-20` — Catch-all domain detected
- `-10` — Role-based address (info@, admin@)
- `-50` — Disposable email service
- `-100` — Spam trap (hard zero)

### 📡 6. Real-Time Webhooks
Get **instant notifications** when bulk jobs complete, with configurable webhook endpoints, HMAC signature verification, automatic retries, and full delivery logs.

### 🏗 7. Full SaaS Platform — Not Just a Library
This is a **complete multi-tenant SaaS product** out of the box:
- User registration & authentication (JWT + Sanctum)
- Credit-based billing with **Stripe** integration
- Team workspaces and member management
- Admin dashboard with system metrics
- Full audit logging and security trail

---

## ✨ Core Product Functions

### 📧 Email Validation

#### Single Email Check
Validate one email instantly via API with full result data:
```json
{
  "email": "john@example.com",
  "status": "valid",
  "score": 92,
  "syntax_valid": true,
  "mx_found": true,
  "smtp_valid": true,
  "spf_found": true,
  "dmarc_found": true,
  "catch_all": false,
  "is_disposable": false,
  "is_role_based": false,
  "is_spam_trap": false,
  "mailbox_provider": "gmail",
  "provider_type": "consumer",
  "validation_time_ms": 1842,
  "score_breakdown": {
    "base": 25,
    "mx_found": 20,
    "smtp_valid": 35,
    "spf_found": 7,
    "dmarc_found": 8,
    "trusted_provider": 5
  }
}
```

#### Batch Validation (up to 100 emails)
Submit up to 100 emails in one API call for parallel processing.

#### Bulk File Validation (up to 10 million emails)
Upload CSV, XLSX, or TXT files with progress tracking, cancellation support, and downloadable results.

---

### 🔍 Validation Status Types

| Status | Meaning | Safe to Send? |
|--------|---------|---------------|
| `valid` | SMTP confirmed, mailbox exists | ✅ Yes |
| `invalid` | Mailbox doesn't exist or domain unreachable | ❌ No |
| `catch_all` | Domain accepts all emails (unverifiable) | ⚠️ Risky |
| `disposable` | Temporary/throwaway email service | ❌ No |
| `spam_trap` | Known spam trap or honeypot | 🚫 Never |
| `risky` | Role-based, greylisted, or uncertain | ⚠️ Caution |
| `unknown` | MX found but SMTP blocked/unavailable | ❓ Uncertain |

---

### 🌐 DNS Validation Features
- **MX Record Lookup** — Finds all mail servers with priority ordering
- **A Record Fallback** — Checks A record if no MX exists
- **SPF Validation** — Detects Sender Policy Framework configuration
- **DMARC Validation** — Checks Domain-based Message Authentication policy
- **Smart Caching** — DNS results cached in MySQL + Redis to avoid redundant lookups

### 📬 SMTP Deep Validation
- Direct connection to mail servers on ports **25, 587, 465**
- Full **EHLO → MAIL FROM → RCPT TO** conversation
- **Catch-all detection** using a randomized non-existent address probe
- **Greylist detection** from 4xx temporary responses
- **Multi-MX fallback** — tries all MX records by priority
- Full SMTP conversation **logged to database** for debugging

### 🗑 Disposable Email Detection
- Database of **100,000+ disposable domains** (Mailinator, Guerrilla Mail, 10MinuteMail, etc.)
- Redis-cached for millisecond lookups
- Admin panel to add new disposable domains in real-time

### 🪤 Spam Trap Detection
- Known **spam trap domains** database
- **Honeypot pattern** matching on email addresses
- **Toxic domain** flagging
- Hard-zero score for any spam trap detection

### 🏢 Role-Based Email Detection
Detects generic role-based addresses that typically have poor engagement:
- `info@`, `admin@`, `support@`, `noreply@`, `sales@`, `contact@`
- Fully configurable keyword list via admin panel

### 🏷 Mailbox Provider Detection
Automatically identifies the email provider:
- Gmail, Outlook/Hotmail, Yahoo, iCloud, ProtonMail
- Office 365, G Suite / Google Workspace
- Custom/self-hosted mail servers

---

### 📊 Bulk Validation System

```
Upload File (CSV/XLSX/TXT)
        │
        ▼
   Parse & Queue
   (RabbitMQ)
        │
        ▼
SMTP Workers (x4)          DNS Workers (x2)
  ~100 emails/sec              ~500/sec
        │
        ▼
  Real-time Progress
  (WebSocket/Polling)
        │
        ▼
  Download Results
  (CSV / XLSX / JSON)
```

**Bulk Job Features:**
- Real-time progress percentage tracking
- Pause and cancel support
- Auto-cleanup of expired result files (7 days)
- Export results filtered by status
- Per-job credit deduction

---

### 💳 Credit & Billing System
- **Stripe integration** for subscription plans and one-time credit packs
- Double-entry **credit ledger** — every debit/credit is tracked
- Plan-based monthly credit allocations
- Admin credit adjustment for customer support
- Full transaction history with export

### 🔑 API Key Management
- Multiple API keys per account
- Per-key rate limiting (requests/minute and requests/day)
- Key labels, expiry dates, and permission scopes
- Instant key revocation
- Full API usage logs per key

### 👥 Team Workspaces
- Create teams with shared credit pools
- Invite members via email
- Role-based permissions (owner, admin, member)
- Team-level API keys and usage tracking

### 📡 Webhook System
- Register up to N webhook endpoints per account
- Events: `job.completed`, `job.failed`, `email.validated`
- **HMAC-SHA256 signature** on every delivery
- Automatic retry with exponential backoff
- Full delivery log with request/response bodies
- One-click test delivery from dashboard

---

### 🛠 Admin Control Panel

| Feature | Description |
|---------|-------------|
| 📊 Dashboard | Real-time validation stats, revenue, user growth charts |
| 👤 User Management | Suspend, activate, adjust credits, view history |
| 📋 Plan Management | Create/edit subscription plans and pricing |
| 🌐 Domain Lists | Manage disposable and spam trap domain databases |
| ⚙️ SMTP Servers | Configure and monitor validation server pool |
| 🖥 Worker Monitoring | Real-time worker health, queue depths, throughput |
| 📜 Audit Logs | Complete security trail of all admin actions |
| 💰 Transactions | Full billing history and revenue reports |

---

## 🏗 System Architecture

```
                   ┌──────────────────────────────────┐
                   │          INTERNET TRAFFIC          │
                   └──────────────┬───────────────────┘
                                  │
                   ┌──────────────▼───────────────────┐
                   │    NGINX LOAD BALANCER (L7)        │
                   │  Rate Limiting + SSL Termination   │
                   └──────┬──────────────┬────────────┘
                          │              │
             ┌────────────▼──┐    ┌──────▼────────────┐
             │  API Server 1  │    │  API Server 2      │
             │ (PHP 8.3 FPM)  │    │ (PHP 8.3 FPM)      │
             │ Laravel 12     │    │ Laravel 12         │
             └────────────┬──┘    └──────┬────────────┘
                          │              │
                   ┌──────▼──────────────▼──────┐
                   │       RABBITMQ BROKER        │
                   │  • smtp_validation queue     │
                   │  • dns_validation queue      │
                   │  • bulk_processing queue     │
                   │  • webhooks queue            │
                   │  • reports queue             │
                   └──┬──────┬──────┬────────────┘
                      │      │      │
         ┌────────────▼┐ ┌───▼──┐ ┌▼────────────┐
         │ SMTP Workers │ │ DNS  │ │ Bulk Workers │
         │    (x4)      │ │Workers│ │   (x2)       │
         │ ~100 /sec    │ │ (x2) │ │  10M emails  │
         └─────┬────────┘ └──┬───┘ └──────┬──────┘
               │              │             │
               └──────────────┼─────────────┘
                              │
         ┌────────────────────▼──────────────────────┐
         │                  REDIS                     │
         │  DB0: General  DB1: Cache                 │
         │  DB2: Sessions  DB3: Queue  DB4: Rate Limit│
         └──────────────────────────────┬────────────┘
                                        │
         ┌──────────────────────────────▼────────────┐
         │              MYSQL CLUSTER                  │
         │    Master (Writes) ──► Read Replica         │
         └────────────────────────────────────────────┘
```

---

## 🛠 Technology Stack

### Backend
| Technology | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.3 | Runtime |
| Laravel | 12.x | Web Framework |
| MySQL | 8.0 | Primary Database + Read Replica |
| Redis | 7.2 | Cache, Sessions, Rate Limiting |
| RabbitMQ | 3.12 | Message Queue / Job Broker |
| Nginx | 1.25 | Load Balancer + Web Server |
| Supervisor | Latest | Worker Process Manager |

### Key PHP Packages
| Package | Purpose |
|---------|---------|
| `laravel/sanctum` | API token authentication |
| `laravel/socialite` | Social OAuth login |
| `laravel/horizon` | Queue monitoring dashboard |
| `tymon/jwt-auth` | JWT authentication |
| `stripe/stripe-php` | Payment processing |
| `phpoffice/phpspreadsheet` | Excel bulk file processing |
| `spatie/laravel-permission` | Role-based access control |
| `spatie/laravel-activitylog` | Audit trail logging |
| `vladimir-yuldashev/laravel-queue-rabbitmq` | RabbitMQ queue driver |

### Frontend
| Technology | Purpose |
|-----------|---------|
| Bootstrap 5.3 | UI Framework |
| Vue.js 3.3 | Interactive Components |
| Chart.js 4.4 | Analytics Charts |

### Infrastructure & Monitoring
| Tool | Purpose |
|------|---------|
| Docker 24+ | Containerization |
| Docker Compose 2+ | Service Orchestration |
| Prometheus 2.47 | Metrics Collection |
| Grafana 10.1 | Monitoring Dashboards |

---

## 🚀 Quick Start

### Prerequisites
- Docker 24+ and Docker Compose 2+
- 4GB RAM minimum (16GB recommended for production)
- Port 80 and 443 available

### 1. Clone the Repository
```bash
git clone https://github.com/itsalexwilliam-pixel/enterprise-platform.git
cd enterprise-platform
```

### 2. Configure Environment
```bash
cp .env.example .env
```

Edit `.env` and set your values:
```env
APP_URL=https://yourdomain.com
DB_PASSWORD=your_strong_password
REDIS_PASSWORD=your_redis_password
RABBITMQ_PASSWORD=your_rabbitmq_password
STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
```

### 3. Start with Docker (Local Dev)
```bash
# Start all services
docker-compose -f docker-compose.local.yml up -d

# Generate application key
docker exec api php artisan key:generate

# Run database migrations + seeders
docker exec api php artisan migrate --seed

# Create storage link
docker exec api php artisan storage:link
```

### 4. Access the Application
| Service | URL |
|---------|-----|
| Web App | http://localhost:8080 |
| Admin Panel | http://localhost:8080/admin |
| API | http://localhost:8080/api/v1 |
| RabbitMQ Manager | http://localhost:15672 |
| Grafana | http://localhost:3000 |

### 5. Default Admin Credentials
```
Email:    admin@example.com
Password: Admin@123456
```
> ⚠️ **Change immediately after first login!**

---

## 📖 API Reference

### Base URL
```
https://yourdomain.com/api/v1
```

### Authentication
All authenticated endpoints require either:
- **API Key header:** `X-API-Key: your_api_key`
- **Bearer token:** `Authorization: Bearer your_token`

---

### 🔐 Authentication Endpoints

#### Register
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

#### Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "SecurePass123!"
}
```

**Response:**
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": { "id": 1, "name": "John Doe", "credits": 1000 }
}
```

---

### ✅ Email Validation Endpoints

#### Validate Single Email
```http
POST /api/v1/validate
X-API-Key: your_api_key
Content-Type: application/json

{
  "email": "john@example.com",
  "options": {
    "smtp_validation": true,
    "skip_cache": false
  }
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "email": "john@example.com",
    "status": "valid",
    "score": 92,
    "risk_level": "low",
    "deliverability": "excellent",
    "syntax_valid": true,
    "mx_found": true,
    "spf_found": true,
    "dmarc_found": true,
    "smtp_valid": true,
    "catch_all": false,
    "is_disposable": false,
    "is_role_based": false,
    "is_spam_trap": false,
    "is_free_email": false,
    "mailbox_provider": "gmail",
    "provider_type": "consumer",
    "validation_time_ms": 1842,
    "from_cache": false
  },
  "credits_remaining": 998
}
```

#### Validate Batch (up to 100 emails)
```http
POST /api/v1/validate/batch
X-API-Key: your_api_key
Content-Type: application/json

{
  "emails": [
    "alice@example.com",
    "bob@invalid-domain.xyz",
    "info@company.com"
  ]
}
```

---

### 📁 Bulk Validation Endpoints

#### Upload File
```http
POST /api/v1/bulk/upload
X-API-Key: your_api_key
Content-Type: multipart/form-data

file: emails.csv    (CSV, XLSX, or TXT — max 100MB)
```

**Response:**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "queued",
  "total_emails": 50000,
  "estimated_minutes": 8
}
```

#### Check Job Status
```http
GET /api/v1/bulk/jobs/{job_id}
X-API-Key: your_api_key
```

**Response:**
```json
{
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "processing",
  "progress": 67,
  "processed": 33500,
  "total": 50000,
  "results": {
    "valid": 28100,
    "invalid": 3200,
    "catch_all": 800,
    "disposable": 950,
    "spam_trap": 12,
    "risky": 438,
    "unknown": 0
  },
  "started_at": "2026-06-06T10:00:00Z",
  "estimated_completion": "2026-06-06T10:08:20Z"
}
```

#### Download Results
```http
GET /api/v1/bulk/jobs/{job_id}/download?format=csv
X-API-Key: your_api_key
```
> Supported formats: `csv`, `xlsx`, `json`

#### Cancel Job
```http
POST /api/v1/bulk/jobs/{job_id}/cancel
X-API-Key: your_api_key
```

---

### 🔑 API Key Management

```http
# List keys
GET /api/v1/account/api-keys

# Create new key
POST /api/v1/account/api-keys
{ "name": "Production Key", "rate_limit": 100 }

# Revoke key
DELETE /api/v1/account/api-keys/{key_id}
```

---

### 📡 Webhook Endpoints

```http
# List webhooks
GET /api/v1/webhooks

# Create webhook
POST /api/v1/webhooks
{
  "url": "https://yourapp.com/webhooks/email",
  "events": ["job.completed", "job.failed"],
  "secret": "your_signing_secret"
}

# Test delivery
POST /api/v1/webhooks/{id}/test

# View delivery logs
GET /api/v1/webhooks/{id}/logs
```

**Webhook Payload:**
```json
{
  "event": "job.completed",
  "job_id": "550e8400-e29b-41d4-a716-446655440000",
  "timestamp": "2026-06-06T10:08:20Z",
  "data": {
    "total": 50000,
    "valid": 42100,
    "invalid": 7900
  }
}
```
> All webhook deliveries include `X-Signature: sha256=HMAC_HASH` for verification.

---

### 💰 Account & Credits

```http
# Get credit balance
GET /api/v1/account/credits

# Get transaction history
GET /api/v1/account/transactions

# Get usage stats
GET /api/v1/account/usage

# Get daily usage breakdown
GET /api/v1/account/usage/daily
```

---

## ⚙️ Configuration

### Core `.env` Settings

#### Application
```env
APP_NAME="Email Validator Pro"
APP_ENV=production
APP_URL=https://yourdomain.com
```

#### SMTP Validation
```env
SMTP_HELO_DOMAIN=mail.yourdomain.com
SMTP_FROM_EMAIL=verify@yourdomain.com
SMTP_TIMEOUT=10
SMTP_CONNECT_TIMEOUT=5
SMTP_MAX_RETRIES=2
```

#### DNS Settings
```env
DNS_TIMEOUT=5
DNS_NAMESERVERS=8.8.8.8,1.1.1.1,9.9.9.9
DNS_CACHE_TTL=3600
```

#### Bulk Processing
```env
BULK_MAX_EMAILS=10000000       # 10 million max per job
BULK_CHUNK_SIZE=1000           # Emails per queue chunk
VALIDATION_CONCURRENT_JOBS=50  # Parallel workers
```

#### Rate Limiting
```env
API_RATE_LIMIT_PER_MINUTE=100
API_RATE_LIMIT_PER_DAY=10000
```

#### Feature Flags
```env
FEATURE_SOCIAL_LOGIN=true
FEATURE_TWO_FACTOR=true
FEATURE_TEAM_ACCOUNTS=true
FEATURE_WHITE_LABEL=true
FEATURE_AI_SCORING=true
```

---

## 🐳 Docker Deployment

### Production Deployment

```bash
# Deploy to production
bash deploy.sh production

# Verify health
curl https://yourdomain.com/api/v1/health
```

### Service Architecture
```yaml
services:
  nginx:        # Load balancer + SSL (port 80/443)
  api:          # PHP 8.3 FPM + Laravel
  worker-smtp:  # SMTP validation workers (x4)
  worker-bulk:  # Bulk file processing workers (x2)
  worker-dns:   # DNS validation workers (x2)
  mysql-master: # Primary database (writes)
  mysql-replica:# Read replica (reads/reports)
  redis:        # Cache + sessions + rate limiting
  rabbitmq:     # Job queue broker
  prometheus:   # Metrics collection
  grafana:      # Monitoring dashboards
```

### Scaling Workers
```bash
# Scale SMTP workers for higher throughput
docker-compose up -d --scale worker-smtp=8

# Each additional worker adds ~25 emails/sec capacity
```

### Windows Quick Start
```bat
# Setup everything
setup.bat

# Start services
start.bat

# View logs
logs.bat

# Stop services
stop.bat

# Rebuild containers
rebuild.bat
```

---

## 🗄 Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `users` | User accounts, roles, credit balance |
| `teams` | Team workspaces |
| `team_members` | Team membership (pivot) |
| `plans` | Subscription plan definitions |
| `subscriptions` | User subscription records |
| `transactions` | Credit ledger (double-entry) |
| `credit_packages` | Purchasable credit bundles |
| `api_keys` | API access keys + metadata |
| `validation_jobs` | Bulk upload job tracking |
| `validation_results` | Individual email results (per-email) |
| `domains` | DNS cache + reputation scores |
| `mx_records` | MX record cache |
| `disposable_domains` | 100K+ disposable email domains |
| `free_email_providers` | Free provider list (Gmail, Yahoo…) |
| `role_keywords` | Role-based prefix list |
| `spam_trap_domains` | Spam trap and honeypot domains |
| `smtp_servers` | SMTP validation server pool |
| `smtp_logs` | SMTP conversation logs |
| `webhooks` | User webhook endpoint config |
| `webhook_deliveries` | Delivery attempts and responses |
| `workers` | Worker heartbeat and health status |
| `audit_logs` | Security audit trail |

---

## 📈 Performance Benchmarks

| Scenario | Result |
|----------|--------|
| Cached result | **< 5ms** |
| Syntax-only fail | **< 10ms** |
| DNS validation fail | **50–200ms** |
| Full SMTP validation | **1–10 seconds** |
| Bulk throughput (4 workers) | **~100 emails/sec** |
| 1 million emails | **~3 hours** |
| 10 million emails | **~28 hours** |
| Scale-out gain | **+25 emails/sec per worker** |

---

## 🔒 Security Features

- **Laravel Sanctum** token authentication
- **JWT** for API authentication
- **HMAC-SHA256** webhook signature verification
- **Rate limiting** at API key level (per-minute and per-day)
- **Role-based access control** via Spatie Permission
- **Audit logging** of all sensitive operations
- **Encrypted sessions** in Redis
- **Input validation** on all API endpoints
- **CORS** configuration with domain whitelist
- **SQL injection** prevention via Eloquent ORM
- **XSS** protection via Laravel's Blade escaping

---

## 🔌 Integrations

| Integration | Purpose |
|-------------|---------|
| **Stripe** | Subscription billing and credit purchases |
| **Mailgun / SMTP** | Transactional emails (verification, reset) |
| **AWS S3** | Bulk file storage (optional) |
| **Google / GitHub OAuth** | Social login (via Socialite) |
| **Prometheus + Grafana** | Monitoring and alerting |
| **RabbitMQ** | Distributed job processing |

---

## 📂 Project Structure

```
enterprise-platform/
├── app/
│   ├── Console/Commands/       # Artisan commands
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin panel controllers
│   │   │   ├── Api/            # REST API controllers
│   │   │   ├── Auth/           # Authentication controllers
│   │   │   └── User/           # User dashboard controllers
│   │   ├── Middleware/         # Request middleware
│   │   └── Requests/           # Form request validation
│   ├── Jobs/                   # Queue jobs
│   ├── Mail/                   # Email notifications
│   ├── Models/                 # Eloquent models
│   ├── Providers/              # Service providers
│   └── Services/
│       └── Validation/         # Core validation engine
│           ├── EmailValidationService.php  # Master orchestrator
│           ├── SyntaxValidator.php         # RFC compliance
│           ├── DnsValidator.php            # MX/SPF/DMARC
│           ├── SmtpValidator.php           # Real SMTP check
│           ├── DisposableDetector.php      # Throwaway detection
│           ├── SpamTrapDetector.php        # Trap/honeypot check
│           ├── MailboxDetector.php         # Provider detection
│           └── ScoringEngine.php           # 0-100 scoring
├── config/
│   └── validation.php          # All validation settings
├── database/
│   ├── migrations/             # Full schema migrations
│   └── seeders/                # Initial data seeders
├── docker/                     # Docker configs per service
├── resources/views/            # Blade templates
├── routes/
│   ├── api.php                 # REST API routes
│   └── web.php                 # Web dashboard routes
├── docker-compose.yml          # Production stack
├── docker-compose.local.yml    # Local dev stack
├── deploy.sh                   # One-command deployment
├── setup.bat                   # Windows setup script
└── .env.example                # Environment template
```

---

## 🚦 Health Check

```bash
curl https://yourdomain.com/api/v1/health
```

```json
{
  "status": "healthy",
  "version": "1.0.0",
  "timestamp": "2026-06-06T10:00:00.000Z"
}
```

---

## 📋 Production Deployment Checklist

### Infrastructure
- [ ] Ubuntu 22.04 LTS, 16GB RAM, 8 vCPUs, 500GB SSD
- [ ] Docker 24+ and Docker Compose 2+ installed
- [ ] Domain DNS A record pointing to server IP
- [ ] SSL certificate configured (Let's Encrypt or paid)
- [ ] Firewall: ports 80, 443 open (22 for SSH only)

### Configuration
- [ ] `.env` file configured from `.env.example`
- [ ] Strong, unique passwords for all services
- [ ] Stripe API keys (live mode) set
- [ ] SMTP server IP not listed in any RBL blacklist
- [ ] `APP_KEY` generated via `php artisan key:generate`

### Deployment
- [ ] Run `bash deploy.sh production`
- [ ] Verify: `curl http://yourdomain.com/api/v1/health`
- [ ] Admin panel accessible and credentials changed
- [ ] Test single email validation via API
- [ ] Test bulk upload with small file (100 emails)

### Monitoring
- [ ] Grafana dashboard accessible and configured
- [ ] Alert rules for worker crashes enabled
- [ ] Alert rules for queue backlog > 10,000 enabled
- [ ] Daily database backup job running

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards
```bash
# Format code
composer lint

# Run tests
composer test

# Static analysis
composer analyse
```

---

## 📄 License

This project is **proprietary software**. All rights reserved.

Unauthorized copying, modification, distribution, or use of this software, via any medium, is strictly prohibited without express written permission from the author.

---

## 🆘 Support

- 📧 Open a [GitHub Issue](https://github.com/itsalexwilliam-pixel/enterprise-platform/issues)
- 📖 Read the [Architecture Docs](ARCHITECTURE.php)
- 🔒 Review [Security Guidelines](SECURITY.php)

---

<div align="center">

**Built with ❤️ for developers who need enterprise-grade email validation without enterprise pricing.**

⭐ **Star this repo if it helps your project!** ⭐

</div>
