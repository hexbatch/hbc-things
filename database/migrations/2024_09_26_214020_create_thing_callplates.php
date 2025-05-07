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
        Schema::create('thing_callplates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('callplate_for_hook_id')
                ->nullable()->default(null)
                ->comment("the hook this callback template is for")
                ->index()
                ->constrained('thing_hooks')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->timestamps();

            $table->integer('ttl_shared')->default(null)->nullable(false)
                ->comment('if set then shared callbacks are discarded if this old in seconds');

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");

            $table->jsonb('callplate_data_template')
                ->nullable()->default(null)
                ->comment("The data and structure that makes up the query|body|form|event|xml".
                    " If missing , the data sent is the constant data from the action, thing, hook if run before, and the result of the action if after.".
                    " Params are nulled keys, are filled in by the above.".
                    " Keys with null values will be removed, including params that are not filled in");

            $table->jsonb('callplate_header_template')
                ->nullable()->default(null)
                ->comment('This is what will be in the header for http calls'.
                    ' Keys with null values will use the values from the action,'.
                    ' or that header removed if not there');

            $table->jsonb('callplate_tags')
                ->nullable()->default(null)
                ->comment("array of string tags, need to match at least one thing tag to be used for that thing. If empty then always used");

        });


        DB::statement("CREATE TYPE type_of_thing_callback_sharing AS ENUM (
            'no_sharing',
            'per_parent',
            'per_tree',
            'global'
            );");

        DB::statement("ALTER TABLE thing_callplates Add COLUMN callplate_sharing_type type_of_thing_callback_sharing NOT NULL default 'no_sharing';");



        DB::statement("CREATE TYPE type_of_thing_callback AS ENUM (
            'disabled',
            'manual',
            'dump',
            'http_get',
            'http_post',
            'http_post_form',
            'http_post_xml',
            'http_post_json',
            'http_put',
            'http_put_form',
            'http_put_xml',
            'http_put_json',
            'http_patch',
            'http_patch_form',
            'http_patch_xml',
            'http_patch_json',
            'http_delete'
            'http_delete_form'
            'http_delete_xml'
            'http_delete_json'
            'code',
            'event_call'
            );");

        DB::statement("ALTER TABLE thing_callplates Add COLUMN callplate_callback_type type_of_thing_callback NOT NULL default 'disabled';");



        DB::statement("ALTER TABLE thing_callplates ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement(  "
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_callplates FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");

        DB::statement('ALTER TABLE thing_callplates ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');


        Schema::table('thing_callplates', function (Blueprint $table) {

            $table->string('address')->nullable(false)
                ->comment('If this is http call, then url, if this is callable, then namespaced class, if event, then event name.');


        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_callplates');
        DB::statement("DROP TYPE type_of_thing_callback;");
        DB::statement("DROP TYPE type_of_thing_callback_sharing;");
    }
};
