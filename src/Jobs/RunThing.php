<?php
namespace Hexbatch\Things\Jobs;



use Hexbatch\Things\Models\Thing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;


class RunThing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Thing $thing
    ) {}


    public function handle(): void
    {

        try {
            $this->thing->runThing();
        } catch (\Exception $e) {
            Log::error(message: $e->getMessage(),context: ['thing_id'=>$this->thing?->id??null,'file'=>$e->getFile(),'line'=>$e->getLine(),'code'=>$e->getCode()]);
        }
    }
}
