<?php
namespace App\Http\Controllers\API\owner;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\DB;
use App\Logging;
use Auth;
use App\ImageUpload;
use App\UserInfo;

class OwnerController extends Controller
{
    public function uploadImage(Request $request)
    {
        $image = $request->file('file');
        $imageName = $image->getClientOriginalName();
        $image->move(public_path('images'),$imageName);
        $imageUpload = new ImageUpload();
        $imageUpload->filename = $imageName;
        $imageUpload->save();
        return response()->json(['error' => '0', 'filename'=>$imageUpload->filename, 'id'=>$imageUpload->id, 'date'=> $imageUpload->updated_at->format('Y-m-d H:i:s')]);
    }

    public function totals(Request $request)
    {
        $images = DB::table('image_uploads')->get();
        $temp = DB::table('settings')->where('param', '=', "default_currencyCode")->get()->first()->value;
        //
        if (UserInfo::getUserRoleId() == 2) { // manager
            $restCount = count(DB::table('manager_rest')->where('user', '=', Auth::user()->id)->join("restaurants", 'restaurants.id', '=', 'manager_rest.restaurant')->get());
            $foodsCount = count(DB::table('manager_rest')->where('user', '=', Auth::user()->id)->join("foods", 'foods.restaurant', '=', 'manager_rest.restaurant')->get());
            $ordersCount = count(DB::table('manager_rest')->where('manager_rest.user', '=', Auth::user()->id)->join("orders", 'orders.restaurant', '=', 'manager_rest.restaurant')->where("send", "1")->
                join("users", 'users.id', '=', 'orders.user')->select('orders.id')->get());
            $total = DB::table('manager_rest')->where('manager_rest.user', '=', Auth::user()->id)->join("orders", 'orders.restaurant', '=', 'manager_rest.restaurant')->where("send", "1")->get()->sum('total');
            //
            $rest = DB::table('manager_rest')->where('user', '=', Auth::user()->id)->join("restaurants", 'restaurants.id', '=', 'manager_rest.restaurant')->
                inRandomOrder()->leftjoin("image_uploads", 'image_uploads.id', '=', 'restaurants.imageid')->select("image_uploads.filename")->get()->first();
            $food = DB::table('manager_rest')->where('user', '=', Auth::user()->id)->join("foods", 'foods.restaurant', '=', 'manager_rest.restaurant')->
                inRandomOrder()->leftjoin("image_uploads", 'image_uploads.id', '=', 'foods.imageid')->select("image_uploads.filename")->get()->first();
            $order = DB::table('manager_rest')->where('manager_rest.user', '=', Auth::user()->id)->join("orders", 'orders.restaurant', '=', 'manager_rest.restaurant')->
                        join('ordersdetails', 'ordersdetails.order', '=', 'orders.id')->inRandomOrder()->select("ordersdetails.image as filename")->get()->first();
        }else {
            $restCount = count(DB::table('restaurants')->get());
            $foodsCount = count(DB::table('foods')->get());
            $ordersCount = count(DB::table('orders')->where("send", "1")->get());
            $total = DB::table('orders')->where("send", "1")->get()->sum('total');
            //
            $rest = DB::table('restaurants')->inRandomOrder()->leftjoin("image_uploads", 'image_uploads.id', '=', 'restaurants.imageid')->
                select("image_uploads.filename")->get()->first();
            $food = DB::table('foods')->inRandomOrder()->leftjoin("image_uploads", 'image_uploads.id', '=', 'foods.imageid')->
                    select("image_uploads.filename")->get()->first();
            $order = DB::table('ordersdetails')->inRandomOrder()->select("ordersdetails.image as filename")->get()->first();
        }

        return response()->json([
            'error' => '0',
            'restaurantImage' => (($rest == null || $rest->filename == null || $rest->filename == "") ? "noimage.png" : $rest->filename),
            'foodImage' => ($food == null ? "noimage.png" : $food->filename),
            'orderImage' => ($order == null ? "noimage.png" : $order->filename),
            'totals' => $total,
            'orders' => $ordersCount,
            'restaurants' => $restCount,
            'foods' => $foodsCount,
            'rightSymbol' => DB::table('settings')->where('param', '=', "rightSymbol")->get()->first()->value,
            'symbolDigits' => DB::table('currencies')->where('code', '=', $temp)->get()->first()->digits,
            'code' => DB::table('currencies')->where('code', '=', $temp)->get()->first()->symbol,
        ]);
    }

    public function getAppSettings(Request $request)
    {
        return response()->json([
            'error' => '0',
            'appLanguage' => DB::table('settings')->where('param', '=', "appLanguage")->get()->first()->value,
        ]);
    }



}
