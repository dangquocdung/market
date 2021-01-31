<?php
namespace App\Http\Controllers;

use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ImageUpload;
use Auth;
use App\Logging;
use App\UserInfo;
use App\Lang;

class ChatController extends Controller
{
    public function getChatMessages(Request $request)
    {
        Logging::logapi("Chat->Get Chat Messages");
        $id = $request->input('user_id');
        
        $msg = DB::table('chat')->where('user', '=', "$id")->orderBy('created_at', 'asc')->get();

        $values = array('read' => 'true', 'updated_at' => new \DateTime());
        DB::table('chat')
            ->where('user', '=', "$id")
            ->where('author', '=', "customer")
            ->update($values);

        $response = [
            'error' => "0",
            'messages' => $msg
        ];
        return response()->json($response, 200);
    }

    public function chat(Request $request)
    {
        if (!Auth::check())
            return \Redirect::route('/');
        Logging::log("Chat Screen");
        
        $users = DB::table('users')->where("role", '>', "2")->
            leftjoin("image_uploads", 'image_uploads.id', '=', 'users.imageid')->
            select('users.*', 'image_uploads.filename as image')->get();

        return view('chat', ['users' => $users]);
    }

    public function chatNewMessage(Request $request)
    {
        if (!Auth::check())
            return \Redirect::route('/');

        Logging::log("Chat->Chat New Messages");

        $user_id = $request->input('user_id');
        $text = $request->input('text');

        $values = array(
            'user' => "$user_id", 'text' => "$text", 'author' => "manager",
            'delivered' => "false", 'read' => "false",
            'created_at' => new \DateTime(),
            'updated_at' => new \DateTime(),
        );
        DB::table('chat')->insert($values);
        $msg = DB::table('chat')->where('user', '=', "$user_id")->orderBy('created_at', 'asc')->get();

        // send notification
        //
        // Send Notifications to user
        //
        $myRequest = new \Illuminate\Http\Request();
        $myRequest->setMethod('POST');
        $myRequest->request->add(['user' => $user_id]);
        $myRequest->request->add(['chat' => 'true']);
        $myRequest->request->add(['title' => Lang::get(477)]); // Chat Message
        $myRequest->request->add(['text' => $text]);
        $defaultImage = DB::table('settings')->where('param', '=', "notify_image")->get()->first()->value;
        $myRequest->request->add(['imageid' => $defaultImage]);
        MessagingController::sendNotify($myRequest);

        $response = [
            'error' => "0",
            'messages' => $msg
        ];
        return response()->json($response, 200);
    }

    public function getChatMessagesNewCount(Request $request)
    {
        if (!Auth::check())
            return \Redirect::route('/');
        
        $count = count(DB::table('chat')->where('read', '=', "false")->where('author', '=', "customer")->get());

        $users = DB::table('chat')->where('author', '=', "customer")->where('read', '=', "false")->selectRaw('user, count(*) as result')->groupBy('user')->get();
        $all = DB::table('chat')->where('author', '=', "customer")->where('read', '=', "true")->selectRaw('user, count(*) as result')->groupBy('user')->get();
        $orders = DB::table('settings')->where('param', '=', "ordersNotifications")->get()->first()->value;

        $response = [
            'error' => "0",
            'count' => $count,
            'users' => $users,
            'all' => $all,
            'orders' => $orders,
        ];
        return response()->json($response, 200);
    }

}