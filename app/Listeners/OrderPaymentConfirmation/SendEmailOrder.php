<?php

namespace App\Listeners\OrderPaymentConfirmation;

use App\Events\OrderPaymentConfirmation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendEmailOrder implements ShouldQueue
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
     * @param \App\Events\ExampleEvent $event
     * @return void
     */
    public function handle(OrderPaymentConfirmation $event)
    {
        $order = $event->order;
        Mail::send(
            'emails.order.send',
            compact('order'),
            function ($mail) use ($order) {
                $mail->to(
                    $order->user_email,
                    $order->user_name_lite
                )
                    ->bcc(explode(';', env('BCC_EMAIL_ORDER')))
                    ->subject('Order ' . $order->order_number . ' - Status ' . $order->status_label);
            }
        );
    }
}
