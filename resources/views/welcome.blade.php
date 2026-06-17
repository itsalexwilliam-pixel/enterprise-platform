<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novelio Technologies LLC - Enterprise Email Verification</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #111827; color: #e8eaf6; font-family: 'Segoe UI', sans-serif; }
        /* Navbar */
        .navbar { background: rgba(17,24,39,0.97); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1rem 0; position: sticky; top: 0; z-index: 1000; }
        .brand-logo img { height: 38px; width: auto; filter: brightness(1.1); }
        .brand-logo-text { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.55); letter-spacing: 0.05em; text-transform: uppercase; display: block; margin-top: 2px; }
        .btn-outline-light { border-color: rgba(255,255,255,0.3); color: #fff; }
        /* Hero */
        .hero { min-height: 90vh; display: flex; align-items: center; background: radial-gradient(ellipse at 60% 50%, rgba(123,47,247,0.25) 0%, transparent 60%), radial-gradient(ellipse at 20% 80%, rgba(0,212,255,0.18) 0%, transparent 50%); padding: 5rem 0; }
        .hero h1 { font-size: clamp(2.2rem, 5vw, 4rem); font-weight: 900; line-height: 1.1; }
        .gradient-text { background: linear-gradient(135deg, #00d4ff, #7b2ff7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .hero p { font-size: 1.1rem; color: rgba(255,255,255,0.72); max-width: 520px; line-height: 1.7; }
        .btn-primary-gradient { background: linear-gradient(135deg, #7b2ff7, #00d4ff); border: none; color: #fff; padding: 0.875rem 2.5rem; border-radius: 50px; font-weight: 700; font-size: 1rem; transition: all 0.3s; }
        .btn-primary-gradient:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(123,47,247,0.5); color: #fff; }
        /* Demo validator */
        .demo-box { background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15); border-radius: 16px; padding: 2rem; }
        .demo-input { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff; border-radius: 10px; padding: 0.875rem 1.25rem; font-size: 1rem; width: 100%; outline: none; transition: border-color 0.2s; }
        .demo-input:focus { border-color: #7b2ff7; background: rgba(255,255,255,0.13); }
        .demo-btn { background: linear-gradient(135deg, #7b2ff7, #00d4ff); border: none; color: #fff; padding: 0.875rem 1.5rem; border-radius: 10px; font-weight: 700; cursor: pointer; white-space: nowrap; transition: opacity 0.2s; }
        .demo-btn:hover { opacity: 0.9; }
        .result-card { background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; padding: 1.25rem; margin-top: 1rem; display: none; }
        .score-circle { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; flex-shrink: 0; }
        .check-row { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; padding: 0.2rem 0; color: rgba(255,255,255,0.8); }
        /* Features */
        .features { padding: 5rem 0; background: rgba(255,255,255,0.02); }
        .features h2 { font-size: 2.2rem; font-weight: 800; text-align: center; margin-bottom: 3rem; }
        .feature-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 2rem; height: 100%; transition: transform 0.2s, border-color 0.2s; }
        .feature-card:hover { transform: translateY(-4px); border-color: rgba(123,47,247,0.5); background: rgba(255,255,255,0.09); }
        .feature-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-bottom: 1.25rem; }
        /* Pricing */
        .pricing { padding: 5rem 0; background: rgba(123,47,247,0.06); }
        .pricing h2 { font-size: 2.2rem; font-weight: 800; text-align: center; margin-bottom: 0.5rem; }
        .plan-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: 16px; padding: 2rem; text-align: center; height: 100%; transition: all 0.3s; }
        .plan-card:hover { background: rgba(255,255,255,0.09); transform: translateY(-3px); }
        .plan-card.featured { background: linear-gradient(135deg, rgba(123,47,247,0.25), rgba(0,212,255,0.1)); border-color: rgba(123,47,247,0.5); transform: scale(1.02); }
        .plan-price { font-size: 2.5rem; font-weight: 900; }
        .plan-period { font-size: 0.9rem; color: rgba(255,255,255,0.55); }
        .plan-feature { padding: 0.4rem 0; border-top: 1px solid rgba(255,255,255,0.1); font-size: 0.9rem; color: rgba(255,255,255,0.8); }
        /* Stats */
        .stats { padding: 4rem 0; background: rgba(0,212,255,0.04); }
        .stat-number { font-size: 2.8rem; font-weight: 900; }
        /* Footer */
        footer { background: rgba(0,0,0,0.25); border-top: 1px solid rgba(255,255,255,0.1); padding: 2.5rem 0; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between w-100">
            <a href="{{ url('/') }}" class="brand-logo text-decoration-none d-inline-block">
                <img src="{{ asset('images/novelio-logo.webp') }}" alt="Novelio Technologies LLC">
                <span class="brand-logo-text">Email Validator Pro</span>
            </a>
            <div class="d-flex gap-3 align-items-center">
                @auth
                <a href="{{ route('user.dashboard') }}" class="btn btn-sm btn-primary-gradient">Dashboard</a>
                @else
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">Login</a>
                <a href="{{ route('register') }}" class="btn btn-sm btn-primary-gradient">Start Free</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="mb-3">
                    <span style="background:rgba(123,47,247,0.15);border:1px solid rgba(123,47,247,0.3);color:#c084fc;padding:0.35rem 1rem;border-radius:20px;font-size:0.8rem;font-weight:600;">
                        <i class="fas fa-bolt me-1"></i> Enterprise Email Validation API
                    </span>
                </div>
                <h1>
                    Validate Emails<br><span class="gradient-text">At Scale.</span>
                </h1>
                <p class="my-4">
                    99.9% accurate email verification. Detect disposable, spam traps, role-based, catch-all and invalid emails before they hurt your deliverability.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="{{ route('register') }}" class="btn btn-primary-gradient">
                        Start Free — 100 Credits <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                    <a href="#demo" class="btn btn-outline-light px-4" style="border-radius:50px;">
                        Live Demo
                    </a>
                </div>
                <div class="mt-4 d-flex gap-4" style="font-size:0.85rem;color:rgba(255,255,255,0.5);">
                    <span><i class="fas fa-check text-success me-1"></i> No credit card</span>
                    <span><i class="fas fa-check text-success me-1"></i> 100 free verifications</span>
                    <span><i class="fas fa-check text-success me-1"></i> REST API</span>
                </div>
            </div>
            <div class="col-lg-6" id="demo">
                <div class="demo-box">
                    <h6 class="mb-3" style="color:rgba(255,255,255,0.7);font-size:0.85rem;text-transform:uppercase;letter-spacing:0.05em;">Try It Now</h6>
                    <div class="d-flex gap-2">
                        <input type="email" id="demoEmail" class="demo-input" placeholder="Enter email address to verify...">
                        <button class="demo-btn" onclick="validateDemo()">
                            <i class="fas fa-search"></i> Check
                        </button>
                    </div>
                    <div class="result-card" id="demoResult">
                        <div class="d-flex align-items-center gap-3">
                            <div class="score-circle" id="scoreCircle">
                                <span id="scoreNum">--</span>
                            </div>
                            <div>
                                <div style="font-size:1.1rem;font-weight:700;" id="resultStatus">Checking...</div>
                                <div style="font-size:0.8rem;color:rgba(255,255,255,0.5);" id="resultEmail"></div>
                            </div>
                        </div>
                        <div class="mt-3" id="checkRows"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="stats">
    <div class="container">
        <div class="row text-center g-4">
            <div class="col-6 col-md-3">
                <div class="stat-number gradient-text">99.9%</div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Accuracy Rate</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-number gradient-text">10M+</div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Emails Validated</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-number gradient-text">&lt;300ms</div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.9rem;">API Response</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-number gradient-text">100K+</div>
                <div style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Disposable Domains</div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="features">
    <div class="container">
        <h2>Everything You Need to <span class="gradient-text">Clean Your List</span></h2>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(0,212,255,0.1);color:#00d4ff;"><i class="fas fa-envelope"></i></div>
                    <h5>Syntax Validation</h5>
                    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">RFC 5321/5322 compliant email format checking with Unicode support.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(123,47,247,0.1);color:#c084fc;"><i class="fas fa-globe"></i></div>
                    <h5>DNS / MX Verification</h5>
                    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Check MX records, SPF, and DMARC for domain deliverability confidence.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(255,193,7,0.1);color:#ffd60a;"><i class="fas fa-server"></i></div>
                    <h5>SMTP Verification</h5>
                    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Communicate with mail servers to confirm the mailbox exists without sending email.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(220,53,69,0.1);color:#ff8a9a;"><i class="fas fa-ban"></i></div>
                    <h5>Disposable Detection</h5>
                    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Block 100,000+ temporary email services like Mailinator, Guerrilla Mail, and more.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(25,135,84,0.1);color:#6feaaa;"><i class="fas fa-spider"></i></div>
                    <h5>Spam Trap Detection</h5>
                    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Identify honeypot addresses and spam traps that can destroy your sender reputation.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon" style="background:rgba(13,202,240,0.1);color:#6ff0ff;"><i class="fas fa-list-check"></i></div>
                    <h5>Bulk Validation</h5>
                    <p style="color:rgba(255,255,255,0.5);font-size:0.9rem;">Upload CSV, XLSX, or TXT files with up to 10 million emails. Download results as CSV.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing -->
<section class="pricing">
    <div class="container">
        <h2>Simple <span class="gradient-text">Credit-Based</span> Pricing</h2>
        <p class="text-center mb-5" style="color:rgba(255,255,255,0.5);">Pay only for what you use. No subscriptions required.</p>
        <div class="row g-4 justify-content-center">
            <div class="col-md-3">
                <div class="plan-card">
                    <h5>Starter</h5>
                    <div class="plan-price">$19</div>
                    <div class="plan-period">5,000 credits</div>
                    <div class="my-3" style="height:1px;background:rgba(255,255,255,0.1);"></div>
                    <div class="plan-feature">Single & Bulk Validation</div>
                    <div class="plan-feature">REST API Access</div>
                    <div class="plan-feature">CSV Export</div>
                    <div class="plan-feature">Email Support</div>
                    <a href="{{ route('register') }}" class="btn btn-outline-light w-100 mt-3" style="border-radius:8px;font-size:0.875rem;">Get Started</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="plan-card featured">
                    <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#c084fc;margin-bottom:0.5rem;">Most Popular</div>
                    <h5>Professional</h5>
                    <div class="plan-price gradient-text">$79</div>
                    <div class="plan-period">50,000 credits</div>
                    <div class="my-3" style="height:1px;background:rgba(255,255,255,0.1);"></div>
                    <div class="plan-feature">All Starter Features</div>
                    <div class="plan-feature">Webhook Notifications</div>
                    <div class="plan-feature">Higher Rate Limits</div>
                    <div class="plan-feature">Priority Support</div>
                    <a href="{{ route('register') }}" class="btn btn-primary-gradient w-100 mt-3" style="border-radius:8px;font-size:0.875rem;background:linear-gradient(135deg,#7b2ff7,#00d4ff);border:none;">Get Started</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="plan-card">
                    <h5>Enterprise</h5>
                    <div class="plan-price">$299</div>
                    <div class="plan-period">500,000 credits</div>
                    <div class="my-3" style="height:1px;background:rgba(255,255,255,0.1);"></div>
                    <div class="plan-feature">All Pro Features</div>
                    <div class="plan-feature">Team Accounts</div>
                    <div class="plan-feature">White Label Option</div>
                    <div class="plan-feature">Dedicated Support</div>
                    <a href="{{ route('register') }}" class="btn btn-outline-light w-100 mt-3" style="border-radius:8px;font-size:0.875rem;">Get Started</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <a href="{{ url('/') }}" class="brand-logo text-decoration-none d-inline-block">
                <img src="{{ asset('images/novelio-logo.webp') }}" alt="Novelio Technologies LLC">
                <span class="brand-logo-text">Email Validator Pro</span>
            </a>
            <div style="color:rgba(255,255,255,0.4);font-size:0.85rem;">© {{ date('Y') }} Novelio Technologies LLC. All rights reserved.</div>
            <div class="d-flex gap-3" style="font-size:0.85rem;">
                <a href="#" style="color:rgba(255,255,255,0.4);" class="text-decoration-none">Privacy</a>
                <a href="#" style="color:rgba(255,255,255,0.4);" class="text-decoration-none">Terms</a>
                <a href="#" style="color:rgba(255,255,255,0.4);" class="text-decoration-none">API Docs</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function validateDemo() {
    const email = document.getElementById('demoEmail').value.trim();
    if (!email) return;
    const result = document.getElementById('demoResult');
    result.style.display = 'block';
    document.getElementById('resultStatus').textContent = 'Checking...';
    document.getElementById('resultEmail').textContent = email;
    document.getElementById('scoreNum').textContent = '...';
    document.getElementById('scoreCircle').style.background = 'rgba(255,255,255,0.1)';
    document.getElementById('checkRows').innerHTML = '';

    // Simulate (real call needs auth)
    await new Promise(r => setTimeout(r, 1200));

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isSyntaxOk = emailRegex.test(email);
    const disposable = ['mailinator.com','guerrillamail.com','tempmail.com','yopmail.com'].includes(email.split('@')[1]);
    const score = disposable ? 15 : (isSyntaxOk ? 72 : 5);
    const status = disposable ? 'Disposable' : (isSyntaxOk ? 'Likely Valid' : 'Invalid Syntax');
    const color = score >= 70 ? '#6feaaa' : score >= 40 ? '#ffd60a' : '#ff8a9a';
    const bg = score >= 70 ? 'rgba(25,135,84,0.2)' : score >= 40 ? 'rgba(255,193,7,0.2)' : 'rgba(220,53,69,0.2)';

    document.getElementById('scoreNum').textContent = score;
    document.getElementById('scoreCircle').style.cssText = `width:70px;height:70px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;font-weight:800;flex-shrink:0;background:${bg};color:${color};`;
    document.getElementById('resultStatus').textContent = status;
    document.getElementById('resultStatus').style.color = color;

    const checks = [
        { label: 'Syntax Valid', ok: isSyntaxOk },
        { label: 'Disposable Domain', ok: !disposable, invert: true },
        { label: 'Sign in to see full SMTP/DNS results', ok: null },
    ];
    document.getElementById('checkRows').innerHTML = checks.map(c =>
        `<div class="check-row">
            ${c.ok === null ? '<i class="fas fa-lock" style="color:#7b2ff7;width:16px;"></i>' :
              (c.invert ? (!c.ok ? '<i class="fas fa-circle-xmark" style="color:#ff8a9a;width:16px;"></i>' : '<i class="fas fa-circle-check" style="color:#6feaaa;width:16px;"></i>') :
              (c.ok ? '<i class="fas fa-circle-check" style="color:#6feaaa;width:16px;"></i>' : '<i class="fas fa-circle-xmark" style="color:#ff8a9a;width:16px;"></i>'))}
            ${c.label}
        </div>`
    ).join('');
}
document.getElementById('demoEmail').addEventListener('keydown', e => { if (e.key === 'Enter') validateDemo(); });
</script>
</body>
</html>
