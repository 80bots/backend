<?php

namespace App\Http\Controllers\Admin;

use App\Bot;
use App\Helpers\CommonHelper;
use App\Http\Controllers\AppController;
use App\Platform;
use App\Tag;
use App\User;
use App\UserInstances;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class BotController extends AppController
{
    public $limit;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bots = Bot::all();
        return view('admin.bots.index', compact('bots'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try{
            $platforms = Platform::get();
            return view('admin.bots.create', compact('platforms'));
        } catch (Throwable $throwable){
            session()->flash('error', $throwable->getMessage());
            return view('admin.bots.create');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try{
            $platform = Platform::findByName($request->input('platform'));

            if (empty($platform)) {
                $platform = Platform::create([
                    'name' => $request->input('platform')
                ]);
            }

            $bot = Bot::create([
                'name'                  => $request->input('name'),
                'platform_id'           => $platform->id ?? null,
                'description'           => $request->input('description'),
                'aws_ami_image_id'      => $request->input('aws_ami_image_id'),
                'aws_ami_name'          => $request->input('aws_ami_name'),
                'aws_instance_type'     => $request->input('aws_instance_type'),
                'aws_startup_script'    => $request->input('aws_startup_script'),
                'aws_custom_script'     => $request->input('aws_custom_script'),
                'aws_storage_gb'        => $request->input('aws_storage_gb'),
                'type'                  => $request->input('type')
            ]);

            $this->addTagsToBot($bot, $request->input('tags'));

            $this->addUsersToBot($bot, $request->input('users'));

            return redirect(route('admin.bots.index'))->with('success', 'Bot Added Successfully');

        } catch(Throwable $throwable) {
            return redirect(route('admin.bots.index'))->with('error', $throwable->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try{
            $bot = Bot::find($id);
            if (! empty($bot)) {
                return view('admin.bots.view', compact('bot', 'id'));
            }
            return redirect(route('admin.bots.index'))->with('error', 'Bot not found');
        } catch (Throwable $throwable){
            return redirect(route('admin.bots.index'))->with('error', $throwable->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try{
            $bot = Bot::find($id);

            if (empty($bot)) {
                return redirect(route('admin.bots.index'))->with('error', 'Bot not found');
            }

            $tags = implode(', ', $bot->tags()->pluck('name')->toArray());
            $users = implode(', ', $bot->users()->pluck('email')->toArray());

            $platforms = Platform::get();

            return view('admin.bots.edit',compact('bot', 'platforms', 'tags', 'users'));

        } catch (Throwable $throwable){
            return redirect(route('admin.bots.index'))->with('error', $throwable->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try{

            $bot = Bot::find($id);

            if (empty($bot)) {
                return redirect(route('admin.bots.index'))
                    ->with('error', 'Bot not found');
            }

            $platform = Platform::findByName($request->input('platform'));

            if (empty($platform)) {
                $platform = Platform::create([
                    'name' => $request->input('platform')
                ]);
            }

            $bot->fill([
                'platform_id' => $platform->id ?? null,
                'bot_name' => $request->input('bot_name'),
                'description' => $request->input('description'),
                'aws_ami_image_id' => $request->input('aws_ami_image_id'),
                'aws_ami_name' => $request->input('aws_ami_name'),
                'aws_instance_type' => $request->input('aws_instance_type'),
                'aws_startup_script' => $request->input('aws_startup_script'),
                'aws_custom_script' => $request->input('aws_custom_script'),
                'aws_storage_gb' => $request->input('aws_storage_gb'),
            ]);

            if ($bot->save()) {
                $this->addTagsToBot($bot, $request->input('tags'));
                $this->addUsersToBot($bot, $request->input('users'));
                return redirect(route('admin.bots.index'))->with('success', 'Bot update successfully');
            }

            return redirect(route('admin.bots.index'))->with('error', 'Bot can not updated successfully');
        } catch (Throwable $throwable){
            return redirect(route('admin.bots.index'))->with('error', $throwable->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            $botObj = Bot::find($id);
            if ($botObj->delete()) {
                return redirect(route('admin.bots.index'))->with('success', 'Bot Delete Successfully');
            }
            return redirect(route('admin.bots.index'))->with('error', 'Bot Can not Deleted Successfully');
        } catch (Throwable $throwable){
            return redirect(route('admin.bots.index'))->with('error', $throwable->getMessage());
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function changeStatus(Request $request)
    {
        try{

            $update = Bot::where('id', '=', $request->id ?? null)
                ->update(['status' => $request->input('status')]);

            if($update){
                return response()->json([
                    'error'     => false,
                    'message'   => ''
                ]);
            }

            return response()->json([
                'error'     => true,
                'message'   => 'Status Change Fail Please Try Again'
            ]);

        } catch (Throwable $throwable){
            return response()->json([
                'error'     => true,
                'message'   => $throwable->getMessage()
            ]);
        }
    }

    public function list($platformId = null)
    {
        if (! $platformId) {
          $this->limit = 5;
        }

        $platforms = new Platform;

        $platforms = $platforms->hasBots($this->limit, $platformId)->paginate(5);

        return view('admin.bots.list',compact('platforms'));
    }

    public function mineBots()
    {
        $userId = Auth::id();
        $instancesId = [];

        $userInstances = UserInstances::findByUserId($userId)->get();
        $bots = Bot::all();

        if (! $userInstances->count()) {
          session()->flash('error', 'Instance Not Found');
        }

        return view('admin.instance.my-bots', compact('userInstances', 'bots'));
    }

    /**
     * @param Bot $bot
     * @param string|null $input
     */
    private function addTagsToBot(Bot $bot, ?string $input): void
    {
        if (! empty($bot) && ! empty($input)) {

            $bot->tags()->detach();

            $tags = CommonHelper::explodeByComma($input);

            $tagsIds = [];

            foreach ($tags as $tag){

                $tagObj = Tag::findByName($tag);

                if (empty($tagObj)) {
                    $tagObj = Tag::create([
                        'name' => $tag
                    ]);
                }

                $tagsIds[] = $tagObj->id ?? null;
            }

            $bot->tags()->attach($tagsIds);
        }
    }

    /**
     * @param Bot $bot
     * @param string|null $input
     */
    private function addUsersToBot(Bot $bot, ?string $input): void
    {
        if (! empty($bot) && ! empty($input)) {
            $bot->users()->detach();
            $emails = CommonHelper::explodeByComma($input);
            $users  = User::whereIn('email', $emails)->pluck('id')->toArray();
            $bot->users()->sync($users);
        }
    }
}
