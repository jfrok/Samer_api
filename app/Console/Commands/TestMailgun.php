<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class TestMailgun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailgun:test {email} {--message=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Mailgun email integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $message = $this->option('message') ?: 'Test email from Laravel Mailgun integration';

        $this->info("Testing Mailgun integration...");
        $this->info("Recipient: {$email}");
        $this->info("Mailer: " . config('mail.default'));
        $this->info("From: " . config('mail.from.address'));
        $this->newLine();

        try {
            Mail::to($email)->send(new TestMail(['message' => $message]));

            $this->info("✅ Email sent successfully!");
            $this->info("Check your email inbox and Mailgun dashboard for confirmation.");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Failed to send email:");
            $this->error($e->getMessage());

            $this->newLine();
            $this->warn("Please check:");
            $this->line("- Your Mailgun credentials in .env file");
            $this->line("- Domain verification in Mailgun dashboard");
            $this->line("- Internet connection");
            $this->line("- Laravel logs in storage/logs/laravel.log");

            return Command::FAILURE;
        }
    }
}
