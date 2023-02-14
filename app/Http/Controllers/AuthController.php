<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Twilio\Rest\Client;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        $this->middleware('auth:api',
         [
            'except' => ['login', 'register']
         ]
        );

    }
    public function register(Request $request)
    {


        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_TOKEN");
        $twilio_number = getenv("TWILIO_FROM");
        // dd($twilio_number);

        $validator = Validator::make($request->all(), [

            'username' => 'required|unique:users',
            'phone' => 'required|unique:users|regex:/^01[0-2,5]{1}[0-9]{8}/i',
            'password' => 'required|confirmed|min:6',

        ]);

        if ($validator->fails()) {

            return $this->apiResponse(400,$validator->errors()->first(),$validator->errors());

        }

        $data = $validator->validated();
        $data['password'] = bcrypt($request->password);
        $data['api_token'] = Str::random(60);
        $data['code'] = rand(10000, 99999);

        $user = User::create($data);

        // dd($user->phone);

        $twilio = new Client($account_sid, $auth_token);

        $toNumber='+2'.$user->phone;


        $twilio->messages->create(
            // the number you'd like to send the message to
            $toNumber,
            [
                // A Twilio phone number you purchased at twilio.com/console
                'from' => $twilio_number,
                // the body of the text message you'd like to send
                'body' => 'Hey'.$user->username
            ]
        );



        return  $this->apiResponse(200, 'Added Successfully',
        ['api_token'=> $user->api_token,
        'client'=>$user]);
    }

    public function login(Request $request)
    {
        $validator= Validator::make($request->all(),[

            'phone'=>'required|regex:/^01[0-2,5]{1}[0-9]{8}/i',
            'password'=>'required',

         ]);
         if ($validator->fails()) {
            return $this->apiResponse(0,$validator->errors()->first(),$validator->errors());
         }

         $user =User::where('phone',$request->phone)->firstOrFail();

         if ($user) {

             if (Hash::check( $request->password , $user->password) ) {

                 return $this->apiResponse(1,'you are in',[
                     'api_token'=> $user->api_token,
                     'user'=>$user
                 ]);
             }else {

                 return $this->apiResponse(0,'The Data You have entered is invalid', $request->password );

             }

         }else {

             return $this->apiResponse(0,'not found',[]);

         }

    }

    public function profile(Request $request)
    {
        $this->validate($request, [
            'token' => 'required'
        ]);

        $user = JWTAuth::authenticate($request->token);
        return $this->apiResponse(1,'the user date', $user);

    }


}
