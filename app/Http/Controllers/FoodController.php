<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\ImageUpload;
use Auth;
use App\Settings;
use App\Logging;
use App\Util;
use App\UserInfo;
use App\Currency;

class FoodController extends Controller
{
    public function load(Request $request)
    {
        if (!Auth::check())
            return \Redirect::route('/');

        Logging::log("Foods Screen");

        return view('foods', []);
    }

    public function add(Request $request)
    {
        if (!Auth::check())
            return \Redirect::route('/');

        $id = $request->input('id');

        $image = $request->input('image', "0");
        $name = $request->input('name') ?: "";
        $published = $request->input('published', "1");
        $delivered = $request->input('delivered', "1");
        $restaurant = $request->input('restaurant', "0");
        $category = $request->input('category', "0");
        $price = $request->input('price') ?: 0.0;
        $discPrice = $request->input('discPrice') ?: 0.0;
        $unit = $request->input('unit') ?: "";
        $package = $request->input('package') ?: 0;
        $weight = $request->input('weight') ?: 0;
        $desc = $request->input('desc') ?: "";
        $ingredients = $request->input('ingredients') ?: "";
        $extras = $request->input('extras') ?: 0;
        $nutritions = $request->input('nutritions') ?: 0;

        $values = array('name' => $name,
            'imageid' => $image, 'price' => $price, 'discountprice' => $discPrice,
            'desc' => $desc, 'restaurant' => $restaurant, 'category' => $category,
            'ingredients' => $ingredients, 'unit' => $unit, 'packageCount' => $package,
            'weight' => $weight, 'canDelivery' => $delivered, 'published' => $published,
            'stars' => '5', 'extras' => $extras, 'nutritions' => $nutritions,
            'updated_at' => new \DateTime());

        if ($id != "0") {
            DB::table('foods')
                ->where('id', $id)
                ->update($values);
            Logging::log2("Food->Update", "Name:" . $name . " Restaurant: " . $restaurant);
        } else {
            $values['created_at'] = new \DateTime();
            DB::table('foods')->insert($values);
            $id = DB::getPdo()->lastInsertId();
            Logging::log2("Food->Create", "Name:" . $name . " Restaurant: " . $restaurant);
        }
        return FoodController::getOne($id);
    }

    public function delete(Request $request)
    {
        if (!Auth::check())
            return \Redirect::route('/');

        $id = $request->input('id');
        $name = DB::table('foods')->where('id', $id)->get()->first()->name;
        if (Settings::isDemoMode()) {
            Logging::log3("Food->Delete", $name, "Abort! This is demo mode");
            return response()->json(['ret' => false, 'text' => 'This is demo app. You can\'t change this section']);
        }
        Logging::log3("Food->Delete", $name, "");

        DB::table('foods')
            ->where('id', $id)
            ->delete();

        return response()->json(['ret' => true]);
    }

    public function foodsGoPage(Request $request)
    {
        if (!Auth::check())
            return response()->json(['error' => "1"]);

        $search = $request->input('search') ?: "";
        $cat = $request->input('cat', "0");
        $rest = $request->input('rest', 0) ?: 0;
        $page = $request->input('page') ?: 1;
        $count = $request->input('count', 10);
        $sortBy = $request->input('sortBy') ?: "id";
        $sortAscDesc = $request->input('sortAscDesc') ?: "asc";
        $sortPublished = $request->input('sortPublished', "1");
        $sortUnPublished = $request->input('sortUnPublished', "1");

        //$user_id = $request->input('user_id') ?: "";
        $petani = DB::table('image_uploads')->get();

        Logging::log("Foods->Go Page " . $page . " search: " . $search);

        $offset = ($page - 1) * $count;

        $searchVisible = "";
        if ($sortPublished != '1' || $sortUnPublished != '1') {
            if ($sortPublished == '1')
                $searchVisible = "published = '1' AND ";
            if ($sortUnPublished == '1')
                $searchVisible = "published = '0' AND ";
        }
        if ($sortPublished == '0' && $sortUnPublished == '0')
            $searchVisible = "published='3' AND ";

        $searchCat = "";
        if ($cat != "0")
            $searchCat = " foods.category=" . $cat . " AND ";

        $searchRest = "";
        if ($rest != "0")
            $searchRest = " foods.restaurant=" . $rest . " AND ";

        $rightJoin = "";
        if (UserInfo::getUserRoleId() > 1) // manager
            $rightJoin = " RIGHT JOIN manager_rest ON manager_rest.user = " . Auth::user()->id . " AND foods.restaurant = manager_rest.restaurant";

        $data = DB::select("SELECT foods.* FROM foods " . $rightJoin . " WHERE " . $searchRest . $searchVisible . $searchCat . " name LIKE '%" . $search . "%' ORDER BY " . $sortBy . " " . $sortAscDesc . " LIMIT " . $count . " OFFSET " . $offset);
        $total = count(DB::select("SELECT foods.* FROM foods " . $rightJoin . " WHERE " . $searchRest . $searchVisible . $searchCat . " name LIKE '%" . $search . "%'"));

        $categories = DB::table('categories')->get();
        $restaurants = DB::table('restaurants')->get();
        foreach ($data as &$value) {
            $filename = DB::table('image_uploads')->where("id", $value->imageid)->get()->first();
            if ($filename != null)
                $value->filename = $filename->filename;
            else
                $value->filename = "noimage.png";
            $value->timeago = Util::timeago($value->updated_at);
            $value->categoryName = "";
            foreach ($categories as &$cat)
                if ($cat->id == $value->category)
                    $value->categoryName = $cat->name;
            $value->restaurantName = "";
            foreach ($restaurants as &$rest)
                if ($rest->id == $value->restaurant)
                    $value->restaurantName = $rest->name;
            $value->priceFull = Currency::makePrice($value->price);
            $value->discountPriceFull = Currency::makePrice($value->discountprice);
        }

        $t = $total / $count;
        if ($total % $count > 0)
            $t++;

        return response()->json(['error' => "0", 'idata' => $data, 'page' => $page, 'pages' => $t, 'total' => $total]);
    }

    public function foodGetInfo(Request $request)
    {
        if (!Auth::check())
            return response()->json(['error' => "1"]);

        $id = $request->input('id', "0");
        if ($id == "0")
            return response()->json(['error' => "4"]);

        return FoodController::getOne($id);
    }

    public function getOne($id)
    {
        $food = DB::table('foods')->where("id", $id)->get()->first();

        $filename = DB::table('image_uploads')->where("id", $food->imageid)->get()->first();
        if ($filename != null)
            $food->filename = $filename->filename;
        else
            $food->filename = "noimage.png";
        $food->timeago = Util::timeago($food->updated_at);

        $categories = DB::table('categories')->get();
        $restaurants = DB::table('restaurants')->get();

        $food->timeago = Util::timeago($food->updated_at);
        $food->categoryName = "";
        foreach ($categories as &$cat)
            if ($cat->id == $food->category)
                $food->categoryName = $cat->name;
        $food->restaurantName = "";
        foreach ($restaurants as &$rest)
            if ($rest->id == $food->restaurant)
                $food->restaurantName = $rest->name;
        $food->priceFull = Currency::makePrice($food->price);
        $food->discountPriceFull = Currency::makePrice($food->discountprice);

        return response()->json(['error' => "0", 'data' => $food]);
    }
}

