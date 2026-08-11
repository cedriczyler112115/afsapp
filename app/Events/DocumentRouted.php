<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DocumentRouted
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $recipientId;
    public array $documentPayload;

    /**
     * Create a new event instance.
     */
    public function __construct(int $recipientId, array $documentPayload)
    {
        $this->recipientId = $recipientId;
        $this->documentPayload = $documentPayload;
    }
}
