<?php

namespace App\Listeners\UserRegistered;

use App\Events\UserRegistered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendActivationEmailUser implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Handle the event.
     *
     * @param \App\Listeners\UserRegistered\ExampleEvent $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        $user = $event->user;
        $user->link_activation = env(
            'WEB_URL'
        ) . '/activation?email=' . $user->email . '&token=' . $user->activation_token;

        Mail::send('emails.user.activation', compact('user'), function ($mail) use ($user) {
            $mail->to($user->email, $user->name)
                ->subject('Account Activation');
        });
    }
}
