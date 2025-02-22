<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('thing_hooks', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('owner_type_id')
                ->nullable()->default(null)
                ->comment("The id of the owner, see type to lookup");


            $table->bigInteger('action_type_id')
                ->nullable()->default(null)
                ->comment("The id of the action, see type to lookup");


            $table->char('action_type',6)
                ->nullable()->default(null)
                ->comment("The type of action");

            $table->char('owner_type',6)
                ->nullable()->default(null)
                ->comment("The type of owner");


            $table->boolean('is_on')->default(true)->nullable(false)
                ->comment('if false then this hook is not used');

            $table->boolean('is_blocking')->default(false)->nullable(false)
                ->comment('if true then thing needs this hook to be successful to continue. Otherwise the cluster reports hook_complete (or errors) after running');

            $table->timestamps();

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");



            $table->jsonb('outgoing_constant_data')
                ->nullable()->default(null)
                ->comment("This is merged with the results of the action, if duplicate keys, the action wins");

            $table->jsonb('outgoing_header')
                ->nullable()->default(null)
                ->comment("This is what will be in the header for http calls,".
                " placeholders can be used in the values, which are filled in by the key in the action result, if present,".
                " or removed if not there");


            $table->string('hooked_thing_callback_url')->nullable()->default(null)
                ->comment('If set, this will be called with the result or error, if null and blocking, then hook needs to be updated manually');

            $table->string('hook_name')->nullable()->default(null)
                ->comment('optional name');

            $table->text('hook_notes')->nullable()->default(null)
                ->comment('optional notes');

            $table->index(['action_type','action_type_id'],'idx_hook_action_type_id');
            $table->index(['owner_type','owner_type_id'],'idx_hook_owner_type_id');

        });

        /*
         * Breakpoints are set to the entire tree if matched, or can manually put a breakpoint on a single thing or collection of them
         */
        DB::statement("CREATE TYPE type_of_thing_hook_mode AS ENUM (
            'none',
            'debug_breakpoint',

            'tree_creation_hook',
            'tree_starting_hook',
            'node_before_running_hook',
            'node_after_running_hook',

            'tree_paused_notice',
            'tree_unpaused_notice',
            'tree_finished_notice',
            'tree_success_notice',
            'tree_failure_notice',
            'node_waiting_notice',
            'node_resume_notice',

            'node_failure_notice',
            'node_success_notice'

            );");

        DB::statement("ALTER TABLE thing_hooks Add COLUMN thing_hook_mode type_of_thing_hook_mode NOT NULL default 'none';");

        DB::statement("ALTER TABLE thing_hooks ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement("
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_hooks FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");

        DB::statement('ALTER TABLE thing_hooks ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_hooks');
        DB::statement("DROP TYPE type_of_thing_hook_mode;");
    }
};
