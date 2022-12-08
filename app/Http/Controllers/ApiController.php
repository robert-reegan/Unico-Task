<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'validation errors'], 400);
        }

        $data = $request->all();
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $response['data'] = $user;
        //$response['token'] = $user->createToken('Myapp');
        return response()->json($response, 400);
    }



    public function login(Request $request)
    {
        if (Auth::attempt(['email' => $request->input('email'), 'password' => $request->input('password')])) {
            $user = Auth::user();
            $response['user'] = $user;
            $response['token'] = $user->createToken('Myapp')->plainTextToken;
            return response()->json($response, 200);
        } else {
            return response()->json(['message' => 'Invalid credentails errors'], 400);
        }
    }

    public function user_details()
    {
        $user = User::all();
        $response['user'] = $user;
        return response()->json($response, 200);
    }
}
