<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\OtpMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOtpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $email,
        private readonly string $otp
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new OtpMail($this->otp));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to send OTP email', [
            'email' => $this->email,
            'error' => $exception->getMessage(),
        ]);
    }
}