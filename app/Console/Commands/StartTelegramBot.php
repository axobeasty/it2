<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SergiX44\Nutgram\Nutgram;

class StartTelegramBot extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:start-telegram-bot';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $bot = new Nutgram('8715696774:AAFy4jBlNTMbQRqQTv4IK3twgqb94l3FEZA');
        // Простое сообщение при получении /start
        $bot->onCommand('start', function (Nutgram $bot) {
            $bot->sendMessage("Привет! Я бот.");
        });

        // Можно обрабатывать любые сообщения
        $bot->onText('привет', function (Nutgram $bot) {
            $bot->sendMessage("И тебе привет!");
        });

        // Запуск long polling
        $bot->run();
    }
}
