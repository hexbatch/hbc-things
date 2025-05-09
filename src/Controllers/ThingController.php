<?php

namespace Hexbatch\Things\Controllers;

use App\OpenApi\ErrorResponse;
use Hexbatch\Things\Helpers\OwnerHelper;
use Hexbatch\Things\Interfaces\IThingOwner;
use Hexbatch\Things\Interfaces\ThingOwnerGroup;
use Hexbatch\Things\Models\Thing;
use Hexbatch\Things\Models\ThingCallback;
use Hexbatch\Things\Models\ThingHook;
use Hexbatch\Things\Models\ThingSetting;
use Hexbatch\Things\OpenApi\Hooks\HookCollectionResponse;
use Hexbatch\Things\OpenApi\Hooks\HookParams;
use Hexbatch\Things\OpenApi\Hooks\HookResponse;
use Hexbatch\Things\Requests\HookRequest;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use OpenApi\Attributes\JsonContent;
use Symfony\Component\HttpFoundation\Response as CodeOf;

class ThingController  {


    #[OA\Get(
        path: '/hbc-things/v1/hooks/list',
        operationId: 'hbc-things.hooks.list',
        description: "",
        summary: 'List all the hooks registered to this owner',
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The hook list',content: new JsonContent(ref: HookCollectionResponse::class)),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
        ]
    )]
    public function hook_list(IThingOwner $owner,ThingOwnerGroup $group) {

        $owners = $group->getOwners();
        $combined_owners = OwnerHelper::addToOwnerArray($owner,$owners);
        /** @var ThingHook[] $hooks */
        $hooks = ThingHook::buildHook(owners: $combined_owners)->get();
        return response()->json(new HookCollectionResponse(hooks: $hooks), CodeOf::HTTP_OK);
    }


    #[OA\Get(
        path: '/hbc-things/v1/hooks/admin/list',
        operationId: 'hbc-things.hooks.admin.list',
        description: "",
        summary: 'List all the hooks',
        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented'),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_hook_list(Request $request) {
        $action_type = $request->query->getString('action_type');
        $action_id =   $request->query->getInt('action_id');
        $owner_type =  $request->query->getString('owner_type');
        $owner_id =    $request->query->getInt('owner_id');
        $tags =        $request->get('tags');

        /** @var ThingHook[] $hooks */
        $hooks = ThingHook::buildHook(
            action_type: $action_type?:null, action_id: $action_id?:null, owner_type: $owner_type?:null, owner_id: $owner_id?:null, tags: is_array($tags)?:[]
        )->get();
        return response()->json(new HookCollectionResponse(hooks: $hooks), CodeOf::HTTP_OK);
    }

    #[OA\Delete(
        path: '/hbc-things/v1/hooks/admin/{thing_hook}/destroy',
        operationId: 'hbc-things.hooks.admin.destroy',
        description: "",
        summary: 'Removes a hook',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented'),
            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),
            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
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
        security: [['bearerAuth' => []]],
        tags: ['hook','admin'],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The shown hook',content: new JsonContent(ref: HookResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."])),

            new OA\Response( response: CodeOf::HTTP_FORBIDDEN, description: 'When not admin',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_FORBIDDEN,"message"=>"Not an admin."]))
        ]
    )]
    public function admin_hook_show(ThingHook $hook) {
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_OK);
    }


    #[OA\Post(
        path: '/hbc-things/v1/hooks/create',
        operationId: 'hbc-things.hooks.create',
        description: "Make a hook with or without defined callback responses",
        summary: 'Create a new hook',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody( content: [
            new OA\MediaType(mediaType: "multipart/form-data",schema: new  OA\Schema(ref: HookParams::class))
        ] ),
        tags: ['hook'],
        responses: [
            new OA\Response( response: CodeOf::HTTP_CREATED, description: 'The created hook',content: new JsonContent(ref: HookResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_hook_create(IThingOwner $owner,HookRequest $request) {

        $data = HookParams::fromRequest(request: $request);
        $data->setHookOwner($owner);
        $hook = ThingHook::createHook($data);
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_CREATED);
    }


    #[OA\Get(
        path: '/hbc-things/v1/hooks/{thing_hook}/show',
        operationId: 'hbc-things.hooks.show',
        description: "",
        summary: 'Shows information about a hook',
        security: [['bearerAuth' => []]],
        tags: ['hook'],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The shown hook',content: new JsonContent(ref: HookResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_hook_show(ThingHook $hook) {
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_OK);
    }

    #[OA\Patch(
        path: '/hbc-things/v1/hooks/{thing_hook}/edit',
        operationId: 'hbc-things.hooks.edit',
        description: "Affects future hooks",
        summary: 'Edits a hook',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody( content: [
            new OA\MediaType(mediaType: "multipart/form-data",schema: new  OA\Schema(ref: HookParams::class))
        ] ),
        tags: ['hook'],
        responses: [
            new OA\Response( response: CodeOf::HTTP_OK, description: 'The edited hook',content: new JsonContent(ref: HookResponse::class)),

            new OA\Response( response: CodeOf::HTTP_BAD_REQUEST, description: 'When not logged in',
                content: new JsonContent(ref: ErrorResponse::class, example: ["status"=>CodeOf::HTTP_BAD_REQUEST,"message"=>"Unauthenticated."]))
        ]
    )]
    public function thing_hook_edit(ThingHook $hook,HookRequest $request) {
        $request->offsetUnset('is_manual'); //cannot change after creation
        $hook->fill($request->validated());
        $hook->save();
        return response()->json(new HookResponse(hook: $hook), CodeOf::HTTP_OK);
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

    #[OA\Post(
        path: '/hbc-things/v1/callbacks/manual/{thing_callback}/answer',
        operationId: 'hbc-things.callbacks.manual_answer',
        description: "",
        summary: 'Show a callback',

        responses: [
            new OA\Response( response: CodeOf::HTTP_NOT_IMPLEMENTED, description: 'Not yet implemented')
        ]
    )]
    public function manual_answer(Thing $thing,ThingCallback $callback) {
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
