<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\DB;
use App\Logging;

class ReviewsController extends Controller
{
    public function foodAdd(Request $request)
    {
        Logging::logapi("Food Reviews->Add");
        
        $id = auth('api')->user()->id;
        $food = $request->input('food') ?: 0;
        $rate = $request->input('rate') ?: 5;
        $desc = $request->input('desc') ?: "";

        $values = array('user' => $id, 'food' => $food, 'rate' => $rate, 'desc' => $desc,
            'updated_at' => new \DateTime());
        $values['created_at'] = new \DateTime();
        DB::table('foodsreviews')->insert($values);

        $date = new \DateTime();
        $date2 = $date->format('Y-m-d H:i:s');

        $response = [
            'error' => '0',
            'date' => $date2,
        ];
        return response()->json($response, 200);
    }

    public function restaurantAdd(Request $request)
    {
        Logging::logapi("Restaurant Reviews->Add");
        $id = auth('api')->user()->id;
        $restaurant = $request->input('restaurant') ?: 0;
        $rate = $request->input('rate') ?: 5;
        $desc = $request->input('desc') ?: "";

        $values = array('user' => $id, 'restaurant' => $restaurant, 'rate' => $rate, 'desc' => $desc,
            'updated_at' => new \DateTime());
        $values['created_at'] = new \DateTime();
        DB::table('restaurantsreviews')->insert($values);

        $date = new \DateTime();
        $date2 = $date->format('Y-m-d H:i:s');

        $response = [
            'error' => '0',
            'date' => $date2,
        ];
        return response()->json($response, 200);
    }


}
