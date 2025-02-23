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
        Schema::create('thing_callbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owning_hooker_id')
                ->nullable()->default(null)
                ->comment("the hooker that runs this callback")
                ->index()
                ->constrained('thing_hookers')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('callback_callplate_id')
                ->nullable()->default(null)
                ->comment("if generated from a callback tempalte")
                ->index()
                ->constrained('thing_callplates')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('callback_error_id')
                ->nullable()
                ->default(null)
                ->comment("When something goes wrong")
                ->index()
                ->constrained('thing_errors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('callback_http_code')->nullable()->default(null)
                ->comment('When the callback was made, what was the http code from that url');

            $table->timestamps();

            $table->timestamp('callback_run_at')
                ->nullable()->default(null)
                ->comment("Updated when the callback is run, used for seeing if ttl in the hook is valid");

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");

            $table->jsonb('callback_incoming_data')
                ->nullable()->default(null)
                ->comment("The body and the headers combined to be one json object,".
                    " if body only returns primitive, it will be marked with key body. Body xml converted to json");

            $table->jsonb('callback_outgoing_data')
                ->nullable()->default(null)
                ->comment("What is going to be sent in the body or query string or function parameters.".
                    " headers that have placeholders same key will be fill from here");

            $table->text('callback_outgoing_header')
                ->nullable()->default(null)
                ->comment('This is what will be in the header for http calls, dilimited by newlines'.
                    ' placeholders of ${keyname} can be used in the values, which are filled in by the key in the callback_outgoing_data, and the data enetry is removed'.
                    ' or that header removed if not there. This column is encrypted at the php level');

        });


        DB::statement("CREATE TYPE type_of_thing_callback_status AS ENUM (
            'building',
            'waiting',
            'callback_successful',
            'callback_error'
            );");

        DB::statement("ALTER TABLE thing_callbacks Add COLUMN thing_callback_status type_of_thing_callback_status NOT NULL default 'building';");


        DB::statement("ALTER TABLE thing_callbacks Add COLUMN thing_callback_type type_of_thing_callback NOT NULL default 'disabled';");



        DB::statement("ALTER TABLE thing_callbacks ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement(  "
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_callbacks FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");

        DB::statement('ALTER TABLE thing_callbacks ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');


        Schema::table('thing_callbacks', function (Blueprint $table) {



            $table->bigInteger('owner_type_id')
                ->nullable()->default(null)
                ->comment("The id of the owner, see type to lookup");


            $table->char('owner_type',6)
                ->nullable()->default(null)
                ->comment("The type of owner");


            $table->string('callback_url')->nullable()->default(null)
                ->comment('If this is http call, this will be called with the response code set above.');


            $table->string('callback_class')->nullable()->default(null)
                ->comment('If set, this is the namespaced class to call');

            $table->string('callback_function')->nullable()->default(null)
                ->comment('If set, this is the function to call, if no class above, then called as regular function. Params in either case are from the callback_outgoing_data');

            $table->string('callback_event')->nullable()->default(null)
                ->comment('If set, this is the event action name to call.  Params in either case are from the values of the top callback_outgoing_data');

        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_callbacks');
        DB::statement("DROP TYPE type_of_thing_callback_status;");
    }
};
