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

            $table->foreignId('owning_hook_id')
                ->nullable(false)
                ->comment("the hook that spawned this callback")
                ->index()
                ->constrained('thing_hooks')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->foreignId('source_thing_id')
                ->nullable(false)
                ->comment("The thing this is for")
                ->index()
                ->constrained('things')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->foreignId('callback_error_id')
                ->nullable()
                ->default(null)
                ->comment("When something goes wrong")
                ->index()
                ->constrained('thing_errors')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('source_shared_callback_id')
                ->nullable()
                ->default(null)
                ->comment("When this is copied from a shared")
                ->index()
                ->constrained('thing_callbacks')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('manual_alert_callback_id')
                ->nullable()
                ->default(null)
                ->comment("When this is a notificed manual callback, the notifier has null here, and the manual has its id here")
                ->index()
                ->constrained('thing_callbacks')
                ->cascadeOnUpdate()
                ->nullOnDelete();



            $table->integer('callback_http_code')->nullable()->default(null)
                ->comment('When the callback was made, what was the http code from that url');

            $table->timestamps();

            $table->timestamp('callback_run_at')
                ->nullable()->default(null)
                ->comment("Updated when the callback is run, used for seeing if new hook run for shared");

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
                ->comment("What is sent in the body|query|xml|parameters");

            $table->jsonb('callback_outgoing_header')
                ->nullable()->default(null)
                ->comment('This is in the header for http calls'.
                    ' the outgoing header is from the callplate, and placeholders are filled in, '.
                    ' or the key removed if placeholder not found.');

        });


        DB::statement("CREATE TYPE type_of_thing_callback_status AS ENUM (
            'building',
            'waiting',
            'callback_successful',
            'callback_error'
            );");

        DB::statement("ALTER TABLE thing_callbacks Add COLUMN thing_callback_status type_of_thing_callback_status NOT NULL default 'building';");



        DB::statement("ALTER TABLE thing_callbacks ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement(  "
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_callbacks FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");

        DB::statement('ALTER TABLE thing_callbacks ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');



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
