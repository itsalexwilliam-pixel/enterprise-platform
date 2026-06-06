<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reset Your Password');
    }

    public function content(): Content
    {
        $resetUrl = url("/auth/reset-password?token={$this->token}&email=" . urlencode($this->user->email));
        $year = date('Y');

        return new Content(
            htmlString: <<<HTML
            <!DOCTYPE html>
            <html>
            <head><meta charset="UTF-8"><style>
                body{font-family:'Segoe UI',Arial,sans-serif;background:#0a0a14;color:#e0e0e8;margin:0;padding:20px;}
                .container{max-width:520px;margin:0 auto;background:#1a1a2e;border:1px solid rgba(255,255,255,0.08);border-radius:12px;overflow:hidden;}
                .header{background:linear-gradient(135deg,#ff6b6b,#7b2ff7);padding:32px;text-align:center;}
                .header h1{color:#fff;margin:0;font-size:1.4rem;font-weight:800;}
                .body{padding:32px;}
                .btn{display:inline-block;background:linear-gradient(135deg,#7b2ff7,#00d4ff);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;margin:20px 0;}
                .footer{padding:20px 32px;border-top:1px solid rgba(255,255,255,0.08);font-size:0.78rem;color:rgba(255,255,255,0.3);text-align:center;}
                p{line-height:1.7;color:rgba(255,255,255,0.7);}
                .warning-box{background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:8px;padding:12px 16px;margin:16px 0;font-size:0.85rem;color:#ffd60a;}
            </style></head>
            <body>
            <div class="container">
                <div class="header"><h1>🔐 Password Reset</h1></div>
                <div class="body">
                    <p>Hi <strong style="color:#fff;">{$this->user->name}</strong>,</p>
                    <p>We received a request to reset the password for your Email Validator Pro account. Click the button below to set a new password.</p>
                    <div style="text-align:center;">
                        <a href="{$resetUrl}" class="btn">Reset My Password</a>
                    </div>
                    <div class="warning-box">⚠️ This link expires in <strong>1 hour</strong>. If you didn't request a password reset, please ignore this email.</div>
                    <p style="font-size:0.82rem;color:rgba(255,255,255,0.3);">If the button doesn't work, copy and paste this link:<br>
                    <a href="{$resetUrl}" style="color:#00d4ff;word-break:break-all;">{$resetUrl}</a></p>
                </div>
                <div class="footer">© {$year} Email Validator Pro · This reset link was requested from your account.</div>
            </div>
            </body>
            </html>
            HTML
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
