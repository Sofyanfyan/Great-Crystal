<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\BillController;
use App\Http\Controllers\MailController;
use App\Http\Controllers\Notification\NotificationBillCreated;
use Carbon\Carbon;
use Illuminate\Console\Command;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class CreateSppCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $signature = 'spp:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command create spp every month';

    /**
     * Execute the console command.
     */
    public function handle() : void
    {
       
      info("Spp Job running at ". Carbon::now()->setTimezone('Asia/Jakarta'));

      $notification = new NotificationBillCreated;
      $notification->spp();

    }
}