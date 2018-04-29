<?php

namespace App\Http\Controllers;
use App\User as User;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Facades\Hash;

class IndexController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth',['except' => ['login']]);
    }

    public function login(Request $request){
        $this->validate($request,[
            'password' => 'required|string|min:6|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        try{
            $user =User::where([['email' , '=' , $request->email],['password' , '=' , $request->password]])->first();
            if($user){
                $response = [
                    'status' => 'Ok',
                    'email' => 'Ok',
                    'password' => 'Ok',
                    'user' => $user
                ];
                return json_encode($response);
            }
            else{
                $emailMatch = User::where([['email' , '=' , $request->email]])->first();
                $passwordMatch = User::where([['password' , '=' , $request->password]])->first();
                if(!$emailMatch && !$passwordMatch){
                    $response = [
                        'status' => 'Unauthorized',
                        'email' => 'Not Found',
                        'password' => 'Not Found',
                        'api_token' => 'Forbidden'
                    ];
                    return json_encode($response);
                }
                elseif(!$emailMatch){
                    $response = [
                        'status' => 'Unauthorized',
                        'email' => 'Not Found',
                        'password' => 'Ok',
                        'api_token' => 'Forbidden'
                    ];
                    return json_encode($response);
                }
                else{$response = [
                    'status' => 'Unauthorized',
                    'email' => 'Ok',
                    'password' => 'Not Found',
                    'api_token' => 'Forbidden'
                ];
                    return json_encode($response);
                }
            }
        }catch(Exception $exception){
            $response = [
                'status' => 'Internal Server Error',
                'exception' =>$exception
            ];
            return json_encode($response);
        }
    }

    public function register(Request $request){
        $this->validate($request,[
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|max:255',
            'role' =>'required',
        ]);
        try{
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = $request->password;
            $user->role = json_encode($request->role);
            $user->save();
            $user = User::orderBy('id','DESC')->first();
            $token = Hash::make($user->id.mt_rand(1000,10000).time());
            $user->api_token = $token;
            $user->save();
            $user = User::orderBy('id','DESC')->first();

            $response = [
                'status' => 'Ok',
                'user' =>$user
            ];
            return json_encode($response,200);
        }catch(Exception $exception){
            $response = [
                'status' => 'Internal Server Error',
                'exception' =>$exception
            ];
            return json_encode($response);
        }
    }
}
