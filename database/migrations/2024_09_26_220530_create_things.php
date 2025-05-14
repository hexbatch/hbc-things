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
        Schema::create('things', function (Blueprint $table) {
            $table->id();


            $table->foreignId('parent_thing_id')
                ->nullable()->default(null)
                ->comment("If this is a child")
                ->index()
                ->constrained('things')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('root_thing_id')
                ->nullable()
                ->default(null)
                ->comment("all things in the same tree, including the root, have this set to the root")
                ->index()
                ->constrained('things')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('thing_error_id')
                ->nullable()
                ->default(null)
                ->comment("When something goes wrong")
                ->index()
                ->constrained('thing_errors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();




            $table->bigInteger('action_type_id')
                ->nullable()->default(null)
                ->comment("The id of the action, see type to lookup");

            $table->bigInteger('owner_type_id')
                ->nullable()->default(null)
                ->comment("The id of the owner, see type to lookup");



            $table->integer('thing_priority')
                ->nullable(false)->default(0)
                ->comment("the higher priority will run first, equal will run at the same time")
                ->index()
            ;






            $table->timestamps();

            $table->timestamp('thing_start_after')->nullable()->default(null)
                ->comment('if set, then this will be done after the time, and not before');

            $table->timestamp('thing_invalid_after')->nullable()->default(null)
                ->comment('if set, then this thing will return false to its parent if the time its processed is after');

            $table->timestamp('thing_started_at')->nullable()->default(null)
                ->comment('if set, then this thing started processing at this time');

            $table->timestamp('thing_ran_at')->nullable()->default(null)
                ->comment('if set, then this when the thing last ran at');


            $table->boolean('is_async')
                ->nullable(false)->default(0)
                ->comment("if true, then will not complete immediately");

            $table->boolean('is_signalling_when_done')
                ->nullable(false)->default(0)
                ->comment("if true, then will try to call the parent when its logic is done");


            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");

            $table->jsonb('thing_tags')
                ->nullable()->default(null)
                ->comment("array of string tags to match up with the hook tags");

        });



        DB::statement("CREATE TYPE type_of_thing_status AS ENUM (
            'thing_building',
            'thing_pending',
            'thing_running',
            'thing_short_circuited',
            'thing_success',
            'thing_fail',
            'thing_invalid',
            'thing_error'
            );");

        DB::statement("ALTER TABLE things Add COLUMN thing_status type_of_thing_status NOT NULL default 'thing_building';");


        Schema::table('things', function (Blueprint $table) {
            $table->index(['thing_status','thing_start_after']);

            $table->string('action_type',30)
                ->nullable()->default(null)
                ->comment("The type of action");

            $table->string('owner_type',30)
                ->nullable()->default(null)
                ->comment("The type of owner");


            $table->index(['action_type','action_type_id'],'idx_thing_action');
            $table->index(['owner_type','owner_type_id'],'idx_thing_owner');
        });


        DB::statement('ALTER TABLE things ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');


        DB::statement("ALTER TABLE things ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement("
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON things FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('things');
        DB::statement("DROP TYPE type_of_thing_status;");

    }
};
