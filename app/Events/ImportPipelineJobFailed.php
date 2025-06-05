<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ImportPipelineJobFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $jobName;

    public $errorDetails;

    public $jobData;

    public $batchId;

    public function __construct(string $jobName, Throwable $exception, array $jobData = [], ?string $batchId = null)
    {
        $this->jobName = $jobName;
        $this->errorDetails = [
            'message' => $exception->getMessage(),
            'class' => get_class($exception),
            'timestamp' => now()->format('Y-m-d H:i:s T'),
        ];
        $this->jobData = $jobData;
        $this->batchId = $batchId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
