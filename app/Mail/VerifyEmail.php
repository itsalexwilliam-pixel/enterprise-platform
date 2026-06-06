<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Verify Your Email Address');
    }

    public function content(): Content
    {
        $verifyUrl = url('/auth/verify-email?token=' . $this->user->email_verification_token);

        return new Content(
            htmlString: <<<HTML
            <!DOCTYPE html>
            <html>
            <head><meta charset="UTF-8"><style>
                body{font-family:'Segoe UI',Arial,sans-serif;background:#0a0a14;color:#e0e0e8;margin:0;padding:20px;}
                .container{max-width:520px;margin:0 auto;background:#1a1a2e;border:1px solid rgba(255,255,255,0.08);border-radius:12px;overflow:hidden;}
                .header{background:linear-gradient(135deg,#7b2ff7,#00d4ff);padding:32px;text-align:center;}
                .header h1{color:#fff;margin:0;font-size:1.4rem;font-weight:800;}
                .body{padding:32px;}
                .btn{display:inline-block;background:linear-gradient(135deg,#7b2ff7,#00d4ff);color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:1rem;margin:20px 0;}
                .footer{padding:20px 32px;border-top:1px solid rgba(255,255,255,0.08);font-size:0.78rem;color:rgba(255,255,255,0.3);text-align:center;}
                p{line-height:1.7;color:rgba(255,255,255,0.7);}
            </style></head>
            <body>
            <div class="container">
                <div class="header"><h1>✉️ Verify Your Email</h1></div>
                <div class="body">
                    <p>Hi <strong style="color:#fff;">{$this->user->name}</strong>,</p>
                    <p>Thanks for signing up to Email Validator Pro! Please click the button below to verify your email address and activate your account.</p>
                    <div style="text-align:center;">
                        <a href="{$verifyUrl}" class="btn">Verify Email Address</a>
                    </div>
                    <p>If you didn't create an account, you can safely ignore this email.</p>
                    <p style="font-size:0.82rem;color:rgba(255,255,255,0.3);">If the button doesn't work, copy and paste this link:<br>
                    <a href="{$verifyUrl}" style="color:#00d4ff;word-break:break-all;">{$verifyUrl}</a></p>
                </div>
                <div class="footer">© {$this->getYear()} Email Validator Pro · You're receiving this because you registered an account.</div>
            </div>
            </body>
            </html>
            HTML
        );
    }

    private function getYear(): string
    {
        return date('Y');
    }

    public function attachments(): array
    {
        return [];
    }
}
