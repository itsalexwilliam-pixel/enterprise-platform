<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'validation_result_id', 'smtp_server_id', 'email', 'mx_host', 'mx_ip',
        'port', 'conversation', 'helo_response', 'mail_from_response',
        'rcpt_to_response', 'rcpt_to_code', 'connection_success', 'helo_success',
        'mail_from_success', 'rcpt_to_success', 'duration_ms', 'error_message', 'created_at',
    ];

    protected $casts = [
        'connection_success' => 'boolean',
        'helo_success'       => 'boolean',
        'mail_from_success'  => 'boolean',
        'rcpt_to_success'    => 'boolean',
        'created_at'         => 'datetime',
    ];
}
