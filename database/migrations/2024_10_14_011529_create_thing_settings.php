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
        Schema::create('thing_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('setting_about_thing_id')
                ->nullable()->default(null)
                ->comment("This was made for a thing and its descendants, when the thing goes away, so does this row")
                ->index()
                ->constrained('things')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();



            $table->bigInteger('owner_type_id')
                ->nullable()->default(null)
                ->comment("The id of the owner, see type to lookup");

            $table->bigInteger('action_type_id')
                ->nullable()->default(null)
                ->comment("The id of the action, see type to lookup");


            $table->integer('setting_rank')->nullable(false)->default(0)
                ->comment('higher rank settings will be used, equal ranks will use the lowest in each category, lower ranks will be ignored');


            $table->integer('descendants_limit')->nullable()->default(null)
                ->comment('if set, then the count of child levels in this tree will calculated, and if over, the building will pause with status of thing_resources');

            $table->integer('data_byte_row_limit')->nullable()->default(null)
                ->comment('if set, then total data size associated with the tree');

            $table->integer('tree_limit')->nullable()->default(null)
                ->comment('if set, then total number trees not completed at any one time');


            $table->integer('backoff_data_policy')->nullable()->default(null)
                ->comment('if set, then if over any limits here or in ancestors, then how long to backoff will be determined here');

            $table->uuid('ref_uuid')
                ->unique()
                ->nullable(false)
                ->comment("used for display and id outside the code");

            $table->timestamps();

            $table->string('action_type',30)
                ->nullable()->default(null)
                ->comment("The type of action");

            $table->string('owner_type',30)
                ->nullable()->default(null)
                ->comment("The type of owner");

            $table->index(['action_type','action_type_id'],'idx_setting_action_type_id');
            $table->index(['owner_type','owner_type_id'],'idx_setting_owner_type_id');

        });

        DB::statement('ALTER TABLE thing_settings ADD CONSTRAINT unsigned_thing_depth_limit CHECK (descendants_limit IS NULL OR  descendants_limit > 0)');
        DB::statement('ALTER TABLE thing_settings ADD CONSTRAINT unsigned_thing_data_limit CHECK (data_byte_row_limit IS NULL OR  data_byte_row_limit > 0)');
        DB::statement('ALTER TABLE thing_settings ADD CONSTRAINT unsigned_thing_tree_limit CHECK (tree_limit IS NULL OR  tree_limit > 0)');

        DB::statement("ALTER TABLE thing_settings ALTER COLUMN created_at SET DEFAULT NOW();");

        DB::statement("
            CREATE TRIGGER update_modified_time BEFORE UPDATE ON thing_settings FOR EACH ROW EXECUTE PROCEDURE update_modified_column();
        ");

        DB::statement('ALTER TABLE thing_settings ALTER COLUMN ref_uuid SET DEFAULT uuid_generate_v4();');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thing_settings');
    }
};
