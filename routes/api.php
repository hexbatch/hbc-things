<?php

use Hexbatch\Things\Controllers\ThingController;
use Illuminate\Support\Facades\Route;


Route::prefix('hbc-things')->group(function () {



    $hbc_middleware = [];
    $my_auth = config('hbc-things.auth_middleware_alias'); //this decides if the owner type/id is valid
    if ($my_auth) {
        $hbc_middleware[] =  $my_auth;
    }

    $hbc_admin = [];
    $my_admin = config('hbc-things.admin_middleware_alias'); //this decides if the owner type/id can do sensitive ops
    if ($my_admin) {
        $hbc_admin[] =  $my_admin;
    }

    Route::prefix('v1')->group(function () use($hbc_middleware,$hbc_admin) {
        Route::middleware($hbc_middleware)->prefix('{owner_type}/{owner_id}')->group(function () use($hbc_admin){


            Route::prefix('things')->group(function () {
                Route::get('list', [ThingController::class, 'thing_list'])->name('hbc-things.things.list');

                Route::prefix('{thing}')->group(function () {
                    Route::get('show', [ThingController::class, 'thing_show'])->name('hbc-things.things.show');
                    Route::get('inspect', [ThingController::class, 'thing_inspect'])->name('hbc-things.things.inspect');
                    Route::put('shortcut', [ThingController::class, 'thing_shortcut'])->name('hbc-things.things.shortcut');
                });
            }); //things

            Route::prefix('settings')->group(function () use($hbc_admin){

                Route::middleware($hbc_admin)->prefix('admin')->group(function() {
                    Route::post('create', [ThingController::class, 'thing_setting_create'])->name('hbc-things.settings.create');

                    Route::prefix('list')->group(function () {
                        Route::get('other/{other_type}/{other_id}', [ThingController::class, 'thing_setting_list_other'])->name('hbc-things.settings.list.other');
                    });

                    Route::prefix('{thing_setting}')->group(function () {
                        Route::delete('remove', [ThingController::class, 'thing_setting_remove'])->name('hbc-things.settings.remove');
                        Route::delete('edit', [ThingController::class, 'thing_setting_edit'])->name('hbc-things.settings.edit');
                    });
                });


                Route::prefix('list')->group(function () {
                    Route::get('action/{action_name}/{action_id}', [ThingController::class, 'thing_setting_list_action'])->name('hbc-things.settings.list.action');
                    Route::get('mine', [ThingController::class, 'thing_setting_list_mine'])->name('hbc-things.settings.list.mine');
                });


                Route::prefix('{thing_setting}')->group(function () {
                    Route::get('show', [ThingController::class, 'thing_setting_show'])->name('hbc-things.settings.show');
                });
            });

            Route::prefix('callbacks')->group(function () {
                Route::prefix('{thing_callback}')->group(function () {
                    Route::post('answer', [ThingController::class, 'thing_callback_answer'])->name('hbc-things.callbacks.answer');
                    Route::get('show', [ThingController::class, 'thing_callback_show'])->name('hbc-things.callbacks.show');
                });
            });


            Route::prefix('hooks')->group(function () use($hbc_admin) {
                Route::get('list', [ThingController::class, 'thing_hook_list'])->name('hbc-things.hooks.list');
                Route::post('create', [ThingController::class, 'thing_hook_create'])->name('hbc-things.hooks.create');

                Route::prefix('{thing_hook}')->group(function () {
                    Route::get('show', [ThingController::class, 'thing_hook_show'])->name('hbc-things.hooks.show');
                    Route::patch('edit', [ThingController::class, 'thing_hook_edit'])->name('hbc-things.hooks.edit');
                    Route::delete('destroy', [ThingController::class, 'thing_hook_destroy'])->name('hbc-things.hooks.destroy');
                });
            });

        });
    });
});


