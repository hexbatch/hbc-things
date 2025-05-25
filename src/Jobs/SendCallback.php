<?php
namespace Hexbatch\Things\Jobs;



use Hexbatch\Things\Enums\TypeOfCallbackStatus;
use Hexbatch\Things\Models\ThingCallback;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\DeleteWhenMissingModels;
use Illuminate\Queue\Attributes\WithoutRelations;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[DeleteWhenMissingModels]
class SendCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public ThingCallback $callback
    ) {}

    public function getCallback(): ThingCallback
    {
        return $this->callback;
    }


    public function handle(): void
    {


        try {
            $this->callback->runCallback();
            if ($this->callback->is_halting_thing_stack) {
                $this->fail();
            }
            else if ($this->callback->thing_callback_status === TypeOfCallbackStatus::CALLBACK_ERROR) {
                if (!$this->callback->owning_hook->is_after) {
                    $this->fail();
                }
            }
        } catch (\Exception $e) {
            Log::error(message: "while running callback: ".$e->getMessage(),context: ['callback_id'=>$this->callback?->id??null,'file'=>$e->getFile(),'line'=>$e->getLine(),'code'=>$e->getCode()]);
            $this->fail($e);
        }
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping($this->callback->ref_uuid))->expireAfter(5*60), new SkipIfBatchCancelled()];
    }
}
