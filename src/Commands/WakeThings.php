<?php

namespace Hexbatch\Things\Commands;

use Carbon\Carbon;
use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\OpenApi\Things\ThingSearchParams;
use Illuminate\Console\Command;

class WakeThings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hex-thing:wake';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For any thing that is waiting and has a timeout, will wake them if past the timeout. Ignores waiting things with no timeout';

    /**
     * Execute the console command.
     * @throws \Exception
     */
    public function handle()
    {
        $counter = 0;
        $params = new ThingSearchParams(wait_until: Carbon::now()->timezone('UTC')->toIso8601String());
        Thing::buildThing(params: $params)->chunk(100, function($records) use(&$counter)  {
            /** @var Thing $thing */
            foreach($records as $thing)
            {
                try {
                    $b_ok = $thing->continueThing();
                    if ($b_ok) {$counter++;}
                } catch (\Exception $e) {
                    $this->error(sprintf("Thing %s has issue on dispatch: %s \n",$thing->ref_uuid,$e));
                }
            }
        });

        if ($counter) {
            $this->info(sprintf("Woke %s things",$counter));
        } else {
            $this->info("No woke things");
        }

    }
}
