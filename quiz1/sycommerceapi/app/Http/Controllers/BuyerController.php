<?php

namespace App\Http\Controllers;

use App\Http\Utility\Common;
use App\Models\Orders;
use App\Models\Products;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class BuyerController extends Controller
{


    //Update user information
    public function Updateuserinfo(Request $request)
    {
        $rules = [
            'username' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ];

        $user = $request->user();

        $input = $request->only('username', 'email', 'phone');
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return Common::Returnerror($validator->messages());
        }
        $affectedrows = User::where('id', $user->id)->update([
            'username' => $request->username,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);
        if ($affectedrows > 0) {
            $user = Auth::user();
            $user->token = $request->user()->createToken('Commerceuser', [$user->type])->plainTextToken;
            return response()->json($user);
        } else {
            return Common::Returnerror("No record was updated");
        }
    }

    //change password
    public function Changepassword(Request $request)
    {

        $rules = [
            'password' => 'required'
        ];
        $user = $request->user();

        $input = $request->only('password');
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->messages()]);
        }
        $affectedrows = User::where('id', $user->id)->update(['password' => Hash::make($request->password)]);

        if ($affectedrows > 0) {
            return Common::Returnsuccess("Password updated successfully");
        } else {
            return Common::Returnerror("There was an internal error contact admin");
        }
    }

    public function Uploadprofilepic(Request $request)
    {
        $profilePic = $request->file('profilepic');
        $user = $request->user();

        if (empty($profilePic)) {
            return Common::Returnerror("No profile picture supplied");
        } else {
            if ($profilePic->isValid()) {
                // Get and store user's profile picture
                $profilePicFilename = time() . '.' . $profilePic->getClientOriginalExtension();
                $profilePicImagePath = public_path('assets');
                $profilePic->move($profilePicImagePath, $profilePicFilename);

                // Update user's profile picture URL in the database
                $user = User::find($user->id); // Assuming you have a 'users' table
                if ($user) {
                    $baseurl = $request->baseurl;
                    $user->profileurl = $baseurl . 'assets/' . $profilePicFilename;
                    $user->save();

                    return json_encode(["profileurl" => $baseurl . 'assets/' . $profilePicFilename]);
                } else {
                    return Common::Returnerror("User not found");
                }
            } else {
                return Common::Returnerror('Invalid profile picture file');
            }
        }
    }

    //sync user details 
    public function Syncuserdetails(Request $request)
    {
        $user = $request->user();
        return $user;
    }

    //fetch buyer orders
    public function Fetchmyorders(Request $request)
    {
        $user = $request->user();
        $allorders = Orders::where('userId', $user->id)->get();

        if (count($allorders) > 0) {
            foreach ($allorders as $order) {
                $productIds = json_decode($order->productIds); // Decode JSON string
                $products = Products::whereIn('id', $productIds)->get(); // Fetch products
                $order->products = $products; // Attach products to the order
            }
            return $allorders;
        } else {
            return Common::Returnerror("You have no orders at the moment");
        }
    }

    // makeorder
    public function Makeorder(Request $request)
    {
        $user = $request->user();
        $products = $request->products;
        $productIds = [];
        $totalAmount = 0;

        foreach ($products as $product) {
            $productId = $product['id'];
            $quantity = $product['quantity'];

            // Retrieve the product's price from the database
            $productPrice = Products::where('id', $productId)->value('price');

            // Increment the total amount with the product's price multiplied by quantity
            $totalAmount += $productPrice * $quantity;

            // Reduce the quantity of the product
            Products::where('id', $productId)->decrement('quantity', $quantity);

            $productIds[] = $productId;
        }

        // Now create an order
        $neworder = new Orders();
        $neworder->userId = $user->id;
        $neworder->productIds = json_encode($productIds);
        $neworder->orderstatus = 'placed';
        $neworder->paymentstatus = 'unpaid';
        $neworder->save();

        // Send the calculated total amount to the Sendstkpush function
        Common::Sendstkpush($totalAmount, $user->phone, $neworder->id);

        return Common::Returnsuccess("Order placed successfully");
    }
}
