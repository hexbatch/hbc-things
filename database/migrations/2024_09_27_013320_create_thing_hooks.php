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
                ->index()
                ->comment("The id of the owner, see type to lookup");


            $table->bigInteger('action_type_id')
                ->nullable()->default(null)
                ->comment("The id of the action, see type to lookup");


            $table->bigInteger('filter_owner_type_id')
                ->nullable()->default(null)
                ->index()
                ->comment("hooks can be set to run on things of an owner");





            $table->boolean('is_on')->default(true)->nullable(false)
                ->comment('if false then this hook is not used');

            $table->boolean('is_blocking')->default(false)->nullable(false)
                ->comment('if false then the callbacks run in parallel. If true then blocks');

            $table->boolean('is_writing_data_to_thing')->default(false)->nullable(false)
                ->comment('if true, and is blocking, then if pre-thing writes to thing, if post thing writes to parent (if exists). No writing for non blocking ');

            $table->boolean('is_sharing')->default(false)->nullable(false)
                ->comment('if true then callback shared among descendants');

            $table->boolean('is_after')->default(false)->nullable(false)
                ->comment('if true then callback is run after the thing is run');

            $table->boolean('is_manual')->default(false)->nullable(false)
                ->comment('if true then callback has manual input');

            $table->integer('ttl_shared')->default(0)->nullable(false)
                ->comment('if set then shared callbacks are discarded if this old in seconds');

            $table->integer('hook_priority')
                ->nullable(false)->default(0)
                ->comment("the higher priority will run their callbacks first first")
                ->index()
            ;

            $table->timestamps();

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");


            $table->jsonb('hook_data_template')
                ->nullable()->default(null)
                ->comment("The data and structure that makes up the query|body|form|event|xml".
                    " If missing , the data sent is the constant data from the action, thing, hook if run before, and the result of the action if after.".
                    " Params are nulled keys, are filled in by the above.".
                    " Keys with null values will be removed, including params that are not filled in");

            $table->jsonb('hook_header_template')
                ->nullable()->default(null)
                ->comment('This is what will be in the header for http calls'.
                    ' Keys with null values will use the values from the action,'.
                    ' or that header removed if not there');

            $table->jsonb('hook_tags')
                ->nullable()->default(null)
                ->comment("array of string tags to match up with the thing tags");

            $table->text('hook_notes')->nullable()->default(null)
                ->comment('optional notes');



        });





        DB::statement("CREATE TYPE type_of_thing_callback AS ENUM (
            'disabled',
            'http_get',
            'http_post',
            'http_post_form',
            'http_post_json',
            'http_put',
            'http_put_form',
            'http_put_json',
            'http_patch',
            'http_patch_form',
            'http_patch_json',
            'http_delete',
            'http_delete_form',
            'http_delete_json',
            'code',
            'event_call'
            );");

        DB::statement("ALTER TABLE thing_hooks Add COLUMN hook_callback_type type_of_thing_callback NOT NULL default 'disabled';");




        /*
         * Breakpoints are set to the entire tree if matched, or can manually put a breakpoint on a single thing or collection of them
         */
        DB::statement("CREATE TYPE type_of_thing_hook_mode AS ENUM (
            'none',

            'node',
            'node_failure',
            'node_success',
            'node_finally'

            );");

        DB::statement("ALTER TABLE thing_hooks Add COLUMN hook_mode type_of_thing_hook_mode NOT NULL default 'none';");




        Schema::table('thing_hooks', function (Blueprint $table) {

            $table->string('action_type',30)
                ->nullable()->default(null)
                ->comment("The type of action");

            $table->string('owner_type',30)
                ->nullable()->default(null)
                ->index()
                ->comment("The type of owner");

            $table->string('filter_owner_type',30)
                ->nullable()->default(null)
                ->index()
                ->comment("Hooks can filter on an owner type");

            $table->index(['action_type','action_type_id'],'idx_thing_hook_action');
            $table->index(['owner_type_id','owner_type'],'idx_thing_hook_owner');
            $table->index(['filter_owner_type_id','filter_owner_type'],'idx_thing_hook_filter_owner');

            $table->string('hook_name')
                ->nullable()->default(null)
                ->unique()
                ->comment('optional name that must be unique if given');

            $table->string('address')->nullable(false)
                ->comment('If this is http call, then url, if this is callable, then namespaced class, if event, then event name.');
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
        DB::statement("DROP TYPE type_of_thing_callback;");
    }
};
