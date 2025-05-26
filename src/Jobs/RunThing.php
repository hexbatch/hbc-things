<?php
namespace Hexbatch\Things\Jobs;



use Hexbatch\Things\Enums\TypeOfThingStatus;
use Hexbatch\Things\Models\Thing;
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
class RunThing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Batchable;


    /**
     * Create a new job instance.
     */
    public function __construct(
        #[WithoutRelations]
        public Thing $thing
    ) {}


    /**
     * @throws \Exception
     */
    public function handle(): void
    {
        try {
            $this->thing->runThing();
            if (!$this->thing->isComplete()) {
                $this->fail();
            }
        } catch (\Exception $e) {
            Log::error(message: "while running thing: ".$e->getMessage(),context: ['thing_id'=>$this->thing?->id??null,'file'=>$e->getFile(),'line'=>$e->getLine(),'code'=>$e->getCode()]);
            try {
                $this->thing->setException($e) ;
            } catch (\Exception $f) {
                Log::error(message: "while in error state and saving : ".$f->getMessage(),context: ['thing_id'=>$this->thing?->id??null,'file'=>$f->getFile(),'line'=>$f->getLine(),'code'=>$f->getCode()]);
            }
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
        return [(new WithoutOverlapping($this->thing->ref_uuid))->expireAfter(180) , new SkipIfBatchCancelled()];
    }
}
