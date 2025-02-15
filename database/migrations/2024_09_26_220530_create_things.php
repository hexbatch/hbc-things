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

            $table->foreignId('thing_error_id')
                ->nullable()
                ->default(null)
                ->comment("When something goes wrong")
                ->index()
                ->constrained('thing_errors')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();


            $table->char('action_type',3)
                ->nullable()->default(null)
                ->comment("The type of action");

            $table->bigInteger('action_type_id')
                ->nullable()->default(null)
                ->comment("The id of the action, see type to lookup");

            $table->integer('action_priority')
                ->nullable(false)->default(0)
                ->comment("the higher priority will run first, equal will run at the same time");

            $table->index(['action_type','action_type_id'],'udx_thing_action_type_id');




            $table->timestamps();

            $table->timestamp('thing_start_at')->nullable()->default(null)
                ->comment('if set, then this will be done after the time, and not before');

            $table->timestamp('thing_invalid_at')->nullable()->default(null)
                ->comment('if set, then this thing will return false to its parent if the time its processed is after');

            $table->timestamp('thing_started_at')->nullable()->default(null)
                ->comment('if set, then this thing started processing at this time');



            $table->smallInteger('debugging_breakpoint')
                ->nullable(false)->default(0)
                ->comment("when breakpoint set for the debugger in the row. Do not need a hook to pause here");




            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");
        });



        DB::statement("CREATE TYPE type_of_thing_status AS ENUM (
            'thing_building',
            'thing_pending',
            'thing_waiting',
            'thing_paused',
            'thing_success',
            'thing_error'
            );");

        DB::statement("ALTER TABLE things Add COLUMN thing_status type_of_thing_status NOT NULL default 'thing_building';");


        Schema::table('things', function (Blueprint $table) {
            $table->index(['thing_status','thing_start_at']);
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
