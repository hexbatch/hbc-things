<?php
namespace Hexbatch\Things\Jobs;



use Hexbatch\Things\Models\ThingResultCallback;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;


class SendCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ThingResultCallback $callback
    ) {}


    public function handle(): void
    {
        $this->callback->callbackUrl();
    }
}
