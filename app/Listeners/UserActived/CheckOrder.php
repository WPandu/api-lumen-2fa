<?php

namespace App\Listeners\UserActived;

use App\Events\UserActived;
use App\Models\Order;
use App\Models\OrderDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CheckOrder implements ShouldQueue
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
     * @param \App\Listeners\UserActived\ExampleEvent $event
     * @return void
     */
    public function handle(UserActived $event)
    {
        $user = $event->user;

        $orders = Order::whereNull('user_id')
            ->where('user_email', $user->email)
            ->get();

        if (!$orders) {
            return;
        }

        foreach ($orders as $order) {
            $order->update([
                'user_id' => $user->id,
                'status' => Order::STATUS_PLACED,
            ]);

            $order->information()->update([
                'placed_at' => now(),
            ]);
        }

        if ($user->addresses()->count() <= 0) {
            return;
        }

        OrderDelivery::whereRelation('order', 'user_id', $user->id)
            ->whereNull('user_address_id')
            ->update([
                'user_address_id' => $user->addresses()->first()->id,
            ]);
    }
}
