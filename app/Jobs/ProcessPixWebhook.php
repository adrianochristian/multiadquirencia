<?php

namespace App\Jobs;

use App\Models\PixTransaction;
use App\Services\Subacquirers\SubacquirerFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPixWebhook implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PixTransaction $pixTransaction,
        public array $webhookPayload
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Processing PIX webhook", [
                'pix_id' => $this->pixTransaction->id,
                'payload' => $this->webhookPayload,
            ]);

            $subacquirerService = SubacquirerFactory::make($this->pixTransaction->subacquirer);
            $parsedData = $subacquirerService->parsePixWebhook($this->webhookPayload);

            $this->pixTransaction->update([
                'status' => $parsedData['status'],
                'payer_name' => $parsedData['payer_name'] ?? $this->pixTransaction->payer_name,
                'payer_document' => $parsedData['payer_document'] ?? $this->pixTransaction->payer_document,
                'paid_at' => $parsedData['paid_at'] ?? ($this->pixTransaction->isPaid() ? now() : null),
                'webhook_payload' => $this->webhookPayload,
            ]);

            Log::info("PIX webhook processed successfully", [
                'pix_id' => $this->pixTransaction->id,
                'status' => $parsedData['status'],
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing PIX webhook", [
                'pix_id' => $this->pixTransaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
