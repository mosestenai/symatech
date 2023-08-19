<?php

namespace App\Http\Controllers;

use App\Http\Utility\Common;
use App\Models\Deletedaccounts;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    //
    //register user function
    public function register(Request $request)
    {
        $rules = [
            'username' => 'required',
            'email' => 'unique:users|required',
            'password'    => 'required',
        ];


        $input     = $request->only('username', 'email', 'password');
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->messages()]);
        }
        $type = 'Buyer';

        //generate promotion code
        $letters = 'abcdefghijklmnopqrstuvwxyz';  // selection of a-z
        $string = '';  // declare empty string
        for ($x = 0; $x < 3; ++$x) {  // loop three times
            $string .= $letters[rand(0, 25)] . rand(0, 9);  // concatenate one letter then one number
        }

        //check if user esists in deleted accounts table
        $restoreduser = Deletedaccounts::where('email', $request->email)->first();

        if ($restoreduser) {
            return Common::Returnerror("Exists");
        } else {
            try {
                $newuser = new User();
                $newuser->username = $request->username;
                $newuser->email = $request->email;
                $newuser->phone = $request->phone;
                $newuser->type = $type;
                $newuser->activatedstatus = 1;
                $newuser->password = Hash::make($request->password);

                $code = random_int(100000, 999999); //generate unique code

                if ($newuser->save()) {
                    $userid = $newuser->id;
                    if (Common::Sendregistercode($request->email, $code)) {
                        return response()->json([
                            "success" => "user registered successfully",
                            "username" => $request->username,
                            "email" => $request->email,
                            "phone" => $request->phone,
                            "code" => $code,
                            "type" => $type
                        ]);
                    } else {
                        $finduser = User::find($userid);
                        $finduser->delete();
                        return Common::Returnerror("There was an internal error .Code 2");
                    }
                } else {
                    return Common::Returnerror("There was an error creating your account");
                }
            } catch (QueryException $e) {
                $errorCode = $e->errorInfo[1];
                if ($errorCode == 1062) {
                    return Common::Returnerror("duplicate phone");
                } else {
                    return Common::Returnerror("There was an internal error.Code " . $errorCode);
                }
            }
        }
    }

    //login user
    public function login(Request $request)
    {
        $rules = [
            'email' => 'required',
            'password'    => 'required',
        ];

        $input     = $request->only('email', 'password');
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return Common::Returnerror($validator->messages());
        }

        if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
            $user = Auth::user();
            $user->token = $request->user()->createToken('Commerceuser', [$user->type])->plainTextToken;
            return response()->json($user);
        } else {
            return Common::Returnerror("wrong login credentials");
        }
    }




    //fetch assets
    public function Fetchassets($filename)
    {
        $path = public_path('assets/' . $filename);
        if (!file_exists($path)) {
            abort(404);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $contentType = $this->getContentType(strtolower($extension));
        $file = file_get_contents($path);

        return response($file, 200)->header('Content-Type', $contentType);
    }

    private function getContentType($extension)
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
                return 'image/' . $extension;
            case 'pdf':
                return 'application/pdf';
            case 'txt':
                return 'text/plain';
            case 'doc':
                return 'application/msword';
            case 'docx':
                return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                // Add more cases for different file extensions as needed
            default:
                return 'application/octet-stream';
        }
    }
}
