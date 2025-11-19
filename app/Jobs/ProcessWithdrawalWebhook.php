<?php

namespace App\Jobs;

use App\Models\Withdrawal;
use App\Services\Subacquirers\SubacquirerFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessWithdrawalWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Withdrawal $withdrawal,
        public array $webhookPayload
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing Withdrawal webhook", [
                'withdrawal_id' => $this->withdrawal->id,
                'payload' => $this->webhookPayload,
            ]);

            $subacquirerService = SubacquirerFactory::make($this->withdrawal->subacquirer);
            $parsedData = $subacquirerService->parseWithdrawalWebhook($this->webhookPayload);

            $this->withdrawal->update([
                'status' => $parsedData['status'],
                'completed_at' => $parsedData['completed_at'] ?? ($this->withdrawal->isCompleted() ? now() : null),
                'webhook_payload' => $this->webhookPayload,
            ]);

            Log::info("Withdrawal webhook processed successfully", [
                'withdrawal_id' => $this->withdrawal->id,
                'status' => $parsedData['status'],
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing Withdrawal webhook", [
                'withdrawal_id' => $this->withdrawal->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
