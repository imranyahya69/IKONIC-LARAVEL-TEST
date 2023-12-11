<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PayoutOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Use the API service to send a payout of the correct amount.
     * Note: The order status must be paid if the payout is successful, or remain unpaid in the event of an exception.
     *
     * @return void
     */
    public function handle(ApiService $apiService)
    {
        DB::beginTransaction();

        try {
            // Call the API service to send the payout
            $apiService->sendPayout($this->order->affiliate->user->email, $this->order->commission_owed);

            // Update the order status to paid
            $this->order->update(['payout_status' => Order::STATUS_PAID]);

            DB::commit();
        } catch (RuntimeException $e) {
            // If an exception occurs, catch it and handle it gracefully
            // Update the order status to unpaid in the event of an exception
            $this->order->update(['payout_status' => Order::STATUS_UNPAID]);

            DB::rollBack();

            // Re-throw the exception to ensure it's logged and available for debugging
            throw $e;
        }
    }
}
