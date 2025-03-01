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

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");

            $table->jsonb('callplate_constant_data')
                ->nullable()->default(null)
                ->comment("This is merged with the outgoing data in the callback, if duplicate keys, the callback wins");

            $table->jsonb('callplate_outgoing_header')
                ->nullable()->default(null)
                ->comment('This is what will be in the header for http calls'.
                    ' placeholders of ${keyname} can be used in the values, which are filled in by the key in the action result, if present,'.
                    ' or that header removed if not there. This column is encrypted at the php level');

        });



        DB::statement("CREATE TYPE type_of_thing_callback AS ENUM (
            'disabled',
            'manual',
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



            $table->bigInteger('owner_type_id')
                ->nullable()->default(null)
                ->comment("The id of the owner, see type to lookup");


            $table->char('owner_type',6)
                ->nullable()->default(null)
                ->comment("The type of owner");


            $table->string('callplate_url')->nullable()->default(null)
                ->comment('If this is http call, this will be called with the response code set above.');


            $table->string('callplate_class')->nullable()->default(null)
                ->comment('If set, this is the namespaced class to call');

            $table->string('callplate_function')->nullable()->default(null)
                ->comment('If set, this is the function to call, if no class above, then called as regular function. Params in either case are from the callback_outgoing_data');

            $table->string('callplate_event')->nullable()->default(null)
                ->comment('If set, this is the event action name to call.  Params in either case are from the values of the top callback_outgoing_data');

            $table->string('callplate_xml_root')->nullable()->default(null)
                ->comment('If set, this applies if the data type is xml, else ignored, and names the root element for the data going out');

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_callplates');
        DB::statement("DROP TYPE type_of_thing_callback;");
    }
};
