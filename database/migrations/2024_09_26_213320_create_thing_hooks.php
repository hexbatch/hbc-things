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





            $table->boolean('is_on')->default(true)->nullable(false)
                ->comment('if false then this hook is not used');


            $table->timestamps();

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");



            $table->jsonb('hook_constant_data')
                ->nullable()->default(null)
                ->comment("This is merged with the callback outgoing data, if duplicate keys, the callback wins");

            $table->jsonb('hook_tags')
                ->nullable()->default(null)
                ->comment("array of string tags to match up with the thing tags");

            $table->text('hook_notes')->nullable()->default(null)
                ->comment('optional notes');

            $table->string('action_type',30)
                ->nullable()->default(null)
                ->comment("The type of action");

            $table->string('owner_type',30)
                ->nullable()->default(null)
                ->comment("The type of owner");

            $table->index(['action_type','action_type_id'],'idx_hook_action_type_id');
            $table->index(['owner_type','owner_type_id'],'idx_hook_owner_type_id');

        });

        /*
         * Breakpoints are set to the entire tree if matched, or can manually put a breakpoint on a single thing or collection of them
         */
        DB::statement("CREATE TYPE type_of_thing_hook_mode AS ENUM (
            'none',

            'tree_creation_hook',
            'tree_starting_hook',
            'node_before_running_hook',
            'node_after_running_hook',

            'tree_resources_notice',
            'node_resources_notice',
            'tree_unpaused_notice',
            'tree_finished_notice',
            'system_tree_results',
            'tree_success_notice',
            'tree_failure_notice',

            'node_failure_notice',
            'node_success_notice'

            );");

        DB::statement("ALTER TABLE thing_hooks Add COLUMN hook_mode type_of_thing_hook_mode NOT NULL default 'none';");


        DB::statement("CREATE TYPE type_of_thing_hook_blocking AS ENUM (
            'none',
            'block',
            'block_add_data_to_parent',
            'block_add_data_to_current'
            );");


        DB::statement("ALTER TABLE thing_hooks Add COLUMN blocking_mode type_of_thing_hook_blocking NOT NULL default 'none';");


        DB::statement("CREATE TYPE type_of_thing_hook_scope AS ENUM (
            'current',
            'ancestor_chain',
            'all_tree',
            'global'
            );");

        DB::statement("ALTER TABLE thing_hooks Add COLUMN hook_scope type_of_thing_hook_scope NOT NULL default 'current';");

        DB::statement("CREATE TYPE type_of_thing_hook_position AS ENUM (
            'any_position',
            'root',
            'any_child',
            'sub_root',
            'leaf'
            );");

        DB::statement("ALTER TABLE thing_hooks Add COLUMN hook_position type_of_thing_hook_position NOT NULL default 'any_position';");


        Schema::table('thing_hooks', function (Blueprint $table) {

            $table->string('hook_name')
                ->nullable()->default(null)
                ->unique()
                ->comment('optional name that must be unique if given');
        });


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
        DB::statement("DROP TYPE type_of_thing_hook_blocking;");
        DB::statement("DROP TYPE type_of_thing_hook_scope;");
        DB::statement("DROP TYPE type_of_thing_hook_position;");
    }
};
