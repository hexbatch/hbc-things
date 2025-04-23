<?php

namespace Hexbatch\Things\Controllers;

use Hexbatch\Things\Interfaces\IThingAction;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Interfaces\ThingOwnerGroup;
use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\Models\ThingSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as CodeOf;
use OpenApi\Attributes as OA;

class ThingController  {


    #[OA\Get(
        path: '/hbc-things/v1/hooks/list',
        operationId: 'hbc-things.hooks.list',
        description: "",
        summary: 'List all the hooks registered to this owner',
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function hook_list(IThingOwner $owner,ThingOwnerGroup $group) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/hooks/admin/list',
        operationId: 'hbc-things.hooks.admin.list',
        description: "",
        summary: 'List all the hooks',
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_hook_list(Request $request) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Delete(
        path: '/hbc-things/v1/hooks/admin/{thing_hook}/destroy',
        operationId: 'hbc-things.hooks.admin.destroy',
        description: "",
        summary: 'Removes a hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_hook_destroy(ThingHook $hook) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/hooks/admin/{thing_hook}/show',
        operationId: 'hbc-things.hooks.admin.show',
        description: "",
        summary: 'Shows information about a hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_hook_show(ThingHook $hook) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Post(
        path: '/hbc-things/v1/hooks/create',
        operationId: 'hbc-things.hooks.create',
        description: "",
        summary: 'Create a new hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_create(IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/hooks/{thing_hook}/show',
        operationId: 'hbc-things.hooks.show',
        description: "",
        summary: 'Shows information about a hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_show(ThingHook $hook,IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Patch(
        path: '/hbc-things/v1/hooks/{thing_hook}/edit',
        operationId: 'hbc-things.hooks.edit',
        description: "Affects future hooks",
        summary: 'Edits a hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_edit(ThingHook $hook,IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Delete(
        path: '/hbc-things/v1/hooks/{thing_hook}/destroy',
        operationId: 'hbc-things.hooks.destroy',
        description: "",
        summary: 'Edits a hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_hook_destroy(ThingHook $hook,IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

















    #[OA\Get(
        path: '/hbc-things/v1/things/list',
        operationId: 'hbc-things.things.list',
        description: "Shows tree status, times, progress",
        summary: 'Lists top things that are owned by user',
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_list(IThingOwner $owner, ThingOwnerGroup $group) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/things/admin/list',
        operationId: 'hbc-things.things.admin.list',
        description: "Shows tree status, times, progress",
        summary: 'Lists top things that are owned by user',
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_admin_list(Request $request) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/things/{thing}/show',
        operationId: 'hbc-things.things.show',
        description: "Lesser detail in decendants",
        summary: 'Shows a thing and its descendants',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_show(Thing $thing,IThingOwner $owner) { //  (a tree)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }



    #[OA\Get(
        path: '/hbc-things/v1/things/admin/{thing}/show',
        operationId: 'hbc-things.things.admin.show',
        description: "Lesser detail in decendants",
        summary: 'Shows a thing and its descendants',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_thing_show(Thing $thing,Request $request) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }







    #[OA\Put(
        path: '/hbc-things/v1/things/{thing}/shortcut',
        operationId: 'hbc-things.things.shortcut',
        description: "If children not run they are shortcut too",
        summary: 'Shortcuts a thing',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_shortcut(Thing $thing,IThingOwner $owner) {  //(if child will return false to parent when it runs, if root then its just gone)
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Post(
        path: '/hbc-things/v1/settings/create',
        operationId: 'hbc-things.settings.create',
        description: "Will apply it to current and new",
        summary: 'Creates a setting',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function thing_setting_create(Request $request) {
        //get all setting values from body
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Post(
        path: '/hbc-things/v1/settings/admin/create',
        operationId: 'hbc-things.settings.admin.create',
        description: "Will apply it to current and new",
        summary: 'Creates a setting',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_setting_create(Request $request) {
        //get all setting values from body
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Delete(
        path: '/hbc-things/v1/things/settings/admin/{thing_setting}/remove',
        operationId: 'hbc-things.settings.admin.remove',
        description: "",
        summary: 'Removes a rate(s) to a thing and its descendants',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_setting_remove(ThingSetting $setting) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Put(
        path: '/hbc-things/v1/things/settings/admin/{thing_setting}/edit',
        operationId: 'hbc-things.settings.admin.edit',
        description: "",
        summary: 'Removes a rate(s) to a thing and its descendants',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_setting_edit(ThingSetting $setting) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/things/settings/admin/{thing_setting}/show',
        operationId: 'hbc-things.settings.admin.show',
        description: "",
        summary: 'Removes a rate(s) to a thing and its descendants',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_setting_show(ThingSetting $setting) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }




    #[OA\Get(
        path: '/hbc-things/v1/settings/admin/list',
        operationId: 'hbc-things.settings.admin.list',
        description: "",
        summary: 'Lists settings applied to another user',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_setting_list(Request $request) { //also search
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/settings/list',
        operationId: 'hbc-things.settings.list',
        description: "",
        summary: 'Lists the settings for me',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function list_settings(IThingOwner $owner, ThingOwnerGroup $group) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/settings/{thing_setting}/settings/show',
        operationId: 'hbc-things.settings.show',
        description: "",
        summary: 'Shows information about a setting',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function setting_show(ThingSetting $setting,IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/callbacks/{thing_callback}/show',
        operationId: 'hbc-things.callbacks.show',
        description: "",
        summary: 'Show a callback',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function callback_show(ThingCallback $callback, IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Get(
        path: '/hbc-things/v1/callbacks/list',
        operationId: 'hbc-things.callbacks.list',
        description: "",
        summary: 'Show a callback',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function list_callbacks( IThingOwner $owner,ThingOwnerGroup $group) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/callbacks/admin/{thing_callback}/show',
        operationId: 'hbc-things.callbacks.admin.show',
        description: "",
        summary: 'Show a callback',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function admin_callback_show(ThingCallback $callback) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }

    #[OA\Post(
        path: '/hbc-things/v1/callbacks/{thing_callback}/complete',
        operationId: 'hbc-things.callbacks.answer',
        description: "",
        summary: 'Complete a callback',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function callback_complete(ThingCallback $callback, IThingOwner $owner) {
        return response()->json([], CodeOf::HTTP_NOT_IMPLEMENTED);
    }


}
