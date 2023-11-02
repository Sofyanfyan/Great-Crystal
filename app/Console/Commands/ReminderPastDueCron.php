<?php

namespace App\Console\Commands;

use App\Http\Controllers\MailController;
use Illuminate\Console\Command;
use Exception;


use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReminderCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $signature = 'reminderPastDue:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check reminder payment past due daily';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        info("Reminder past due Job running at ". now());

        $bill = new MailController;
        $bill->cronReminderPastDue('SPP');
        $bill->cronReminderPastDue('Uang Gedung');
        // $bill->cronReminderPastDue('Book');
        // $bill->cronReminderPastDue('Uniform');
    }
}