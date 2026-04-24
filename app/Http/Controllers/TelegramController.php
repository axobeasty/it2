<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Nutgram;

class TelegramController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(Nutgram $bot)
    {
        $bot->onMessage(function (Nutgram $bot) {
            $bot->sendMessage('You sent a message!');
        });

        $bot->run();
    }
}
