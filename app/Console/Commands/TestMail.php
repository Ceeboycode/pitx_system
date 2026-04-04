<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMail extends Command
{
    protected $signature = 'mail:test {email}';
    protected $description = 'Send a test email to verify SMTP configuration';

    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Sending test email to: {$email}");

        try {
            Mail::raw('✅ Your Gmail SMTP is working correctly in Laravel!', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Laravel SMTP Test');
            });

            $this->info('✅ Email sent successfully!');
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email!');
            $this->error($e->getMessage());
        }
    }
}
