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

    public int $tries;

    public array $backoff;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public PixTransaction $pixTransaction,
        public array $webhookPayload
    ) {
        $this->tries = (int) config('subacquirers.webhook_jobs.tries', 3);
        $this->backoff = config('subacquirers.webhook_jobs.backoff', [60, 300, 900]);
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

            $newStatus = $parsedData['status'] ?? null;

            if ($newStatus === null) {
                Log::warning('PIX webhook without status, skipping', [
                    'pix_id' => $this->pixTransaction->id,
                ]);

                return;
            }

            if ($this->pixTransaction->status === $newStatus) {
                Log::info('Ignoring duplicate PIX webhook with same status', [
                    'pix_id' => $this->pixTransaction->id,
                    'status' => $newStatus,
                ]);

                return;
            }

            if ($this->pixTransaction->isPaid()) {
                Log::info('Ignoring PIX webhook for already paid transaction', [
                    'pix_id' => $this->pixTransaction->id,
                    'current_status' => $this->pixTransaction->status,
                    'incoming_status' => $newStatus,
                ]);

                return;
            }

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
