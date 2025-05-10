<?php

use Hexbatch\Things\Controllers\ThingController;
use Illuminate\Support\Facades\Route;


Route::prefix('hbc-things')->group(function () {

    $hbc_middleware = [];
    $my_auth = config('hbc-things.middleware.auth_alias'); //this decides if the owner type/id is valid
    if ($my_auth) {
        $hbc_middleware[] =  $my_auth;
    }

    $my_user = config('hbc-things.middleware.owner_alias'); //this sets the IThingOwner
    if ($my_user) {
        $hbc_middleware[] =  $my_user;
    }

    $hbc_admin = [];
    $my_admin = config('hbc-things.middleware.owner_alias'); //this decides if the logged-in user can do sensitive ops
    if ($my_admin) {
        $hbc_admin[] =  $my_admin;
    }


    $hbc_thing_viewable = [];
    $my_thing_viewable = config('hbc-things.middleware.thing_viewable_alias'); //view thing
    if ($my_thing_viewable) {
        $hbc_thing_viewable[] =  $my_thing_viewable;
    }

    $hbc_thing_editable = [];
    $my_thing_editable = config('hbc-things.middleware.thing_editable_alias'); //edit thing
    if ($my_thing_editable) {
        $hbc_thing_editable[] =  $my_thing_editable;
    }

    $hbc_thing_listing = [];
    $my_thing_listing = config('hbc-things.middleware.thing_listing_alias'); //edit thing
    if ($my_thing_listing) {
        $hbc_thing_listing[] =  $my_thing_listing;
    }

    $hbc_setting_viewable = [];
    $my_setting_viewable = config('hbc-things.middleware.setting_viewable_alias'); //view setting?
    if ($my_setting_viewable) {
        $hbc_setting_viewable[] =  $my_setting_viewable;
    }

    $hbc_setting_listing = [];
    $my_setting_listing = config('hbc-things.middleware.setting_listing_alias'); //view setting?
    if ($my_setting_listing) {
        $hbc_setting_listing[] =  $my_setting_listing;
    }



    $hbc_hook_viewable = [];
    $my_hook_viewable = config('hbc-things.middleware.hook_viewable_alias'); //see hook?
    if ($my_hook_viewable) {
        $hbc_hook_viewable[] =  $my_hook_viewable;
    }

    $hbc_hook_list = [];
    $my_hook_listing = config('hbc-things.middleware.hook_listing_alias'); //see hook?
    if ($my_hook_listing) {
        $hbc_hook_list[] =  $my_hook_listing;
    }

    $hbc_hook_editable = [];
    $my_hook_editable = config('hbc-things.middleware.hook_editable_alias'); //edit hook?
    if ($my_hook_editable) {
        $hbc_hook_editable[] =  $my_hook_editable;
    }


    Route::prefix('v1')->group(function ()
        use($hbc_middleware,$hbc_admin,
            $hbc_thing_viewable,$hbc_thing_listing,$hbc_thing_editable,
            $hbc_setting_viewable,$hbc_setting_listing,
            $hbc_hook_viewable,$hbc_hook_list,$hbc_hook_editable)
    {
        Route::prefix('callbacks')->group(function () {
            Route::prefix('manual/{thing_callback}')->group(function () {
                Route::post('answer', [ThingController::class, 'manual_answer'])->name('hbc-things.callbacks.manual_answer');
            });
        });

        Route::middleware($hbc_middleware)->group(function ()
            use($hbc_middleware,$hbc_admin,
                $hbc_thing_viewable,$hbc_thing_listing,$hbc_thing_editable,
                $hbc_setting_viewable,$hbc_setting_listing,
                $hbc_hook_viewable,$hbc_hook_list,$hbc_hook_editable)
        {


            Route::prefix('things')->group(function ()
                use($hbc_admin,$hbc_thing_viewable,$hbc_thing_listing,$hbc_thing_editable)
            {


                Route::middleware($hbc_admin)->prefix('admin')->group(function()
                {
                    Route::get('list', [ThingController::class, 'thing_admin_list'])->name('hbc-things.things.admin.list');

                    Route::prefix('{thing}')->group(function () {
                        Route::get('show', [ThingController::class, 'admin_thing_show'])->name('hbc-things.things.admin.show');
                    });
                });

                Route::middleware($hbc_thing_listing)->group(function() {
                    Route::get('list', [ThingController::class, 'thing_list'])->name('hbc-things.things.list');
                });


                Route::prefix('{thing}')->group(function ()
                    use($hbc_thing_viewable,$hbc_thing_editable)
                {

                    Route::middleware($hbc_thing_viewable)->group(function()
                        use($hbc_thing_editable)
                    {
                        Route::get('show', [ThingController::class, 'thing_show'])->name('hbc-things.things.show');
                        Route::middleware($hbc_thing_editable)->group(function() {
                            Route::put('shortcut', [ThingController::class, 'thing_shortcut'])->name('hbc-things.things.shortcut');
                        });
                    });
                });
            }); //things







            Route::prefix('callbacks')->group(function ()
                use($hbc_admin,$hbc_thing_viewable,$hbc_thing_editable,$hbc_thing_listing)
            {
                Route::middleware($hbc_thing_listing)->group(function () {
                    Route::get('list', [ThingController::class, 'list_callbacks'])->name('hbc-things.callbacks.list');
                });

                Route::prefix('{thing_callback}')->group(function ()
                    use($hbc_thing_viewable,$hbc_thing_editable)
                {
                    Route::middleware($hbc_thing_viewable)->group(function()
                        use($hbc_thing_editable) {

                        Route::get('show', [ThingController::class, 'callback_show'])->name('hbc-things.callbacks.show');

                        Route::middleware($hbc_thing_editable)->group(function() {
                            Route::post('complete', [ThingController::class, 'callback_complete'])->name('hbc-things.callbacks.complete');
                        });
                    });
                });

                Route::middleware($hbc_admin)->prefix('admin')->group(function() {
                    Route::prefix('{thing_callback}')->group(function () {
                        Route::get('show', [ThingController::class, 'admin_callback_show'])->name('hbc-things.callbacks.admin.show');
                    });
                });
            });



            Route::prefix('hooks')->group(function ()
                use($hbc_admin,$hbc_hook_viewable,$hbc_hook_editable,$hbc_hook_list)
            {
                Route::middleware($hbc_hook_list)->group(function () {
                    Route::get('list', [ThingController::class, 'hook_list'])->name('hbc-things.hooks.list');
                });
                Route::post('create', [ThingController::class, 'thing_hook_create'])->name('hbc-things.hooks.create');

                Route::prefix('{thing_hook}')->group(function ()
                    use($hbc_hook_viewable,$hbc_hook_editable)
                {

                    Route::middleware($hbc_hook_viewable)->group(function()
                        use($hbc_hook_editable)
                    {
                        Route::get('show', [ThingController::class, 'thing_hook_show'])->name('hbc-things.hooks.show');
                        Route::middleware($hbc_hook_editable)->group(function()
                        {
                            Route::patch('edit', [ThingController::class, 'thing_hook_edit'])->name('hbc-things.hooks.edit');
                            Route::delete('destroy', [ThingController::class, 'thing_hook_destroy'])->name('hbc-things.hooks.destroy');
                        });
                    });


                });

                Route::middleware($hbc_admin)->prefix('admin')->group(function() {
                    Route::get('list', [ThingController::class, 'admin_hook_list'])->name('hbc-things.hooks.admin.list');
                    Route::prefix('{thing_hook}')->group(function () {
                        Route::get('show', [ThingController::class, 'admin_hook_show'])->name('hbc-things.hooks.admin.show');
                        Route::delete('destroy', [ThingController::class, 'admin_hook_destroy'])->name('hbc-things.hooks.admin.destroy');
                    });
                });
            });

        });
    });
});


