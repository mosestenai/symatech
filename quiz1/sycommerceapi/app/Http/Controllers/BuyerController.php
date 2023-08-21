<?php

namespace App\Http\Controllers;

use App\Http\Utility\Common;
use App\Models\Activitylogs;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Transactions;
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
            $quantity = $product['buyquantity'];

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
        $neworder->totalamount = $totalAmount;
        $neworder->save();
        //records activity
        $newactivity = new Activitylogs();
        $newactivity->userId = $user->id;
        $newactivity->action = 'Order placed. Order id' . $neworder->id;
        $newactivity->save();

        $userphone = $user->phone;

        // Check if the phone number starts with "07" or "7"
        if (strpos($userphone, '07') === 0 || strpos($userphone, '7') === 0) {
            // Replace the initial "07" or "7" with "2547"
            $userphone = '2547' . substr($userphone, 2);
        }

        // Send the calculated total amount to the Sendstkpush function
        Common::Sendstkpush($totalAmount, $userphone, $neworder->id);

        return Common::Returnsuccess("Order placed successfully");
    }


    //complete mpesa transaction
    public function Completedarajatrans(Request $request)
    {
        $orderid = $request->query('orderid');
        $token = $request->input('token');
        if ($token != 'sycommercetest') {
            return response()->json(['message' => 'Invalid authorization'], 403);
        }
        //get the order details 
        $order = Orders::find($orderid);

        //get the contents sent from mpesa
        $json = $request->getContent();
        $data = json_decode($json, true);

        $MerchantRequestID = $data['Body']['stkCallback']['MerchantRequestID'];
        $CheckoutRequestID = $data['Body']['stkCallback']['CheckoutRequestID'];
        $ResultCode = $data['Body']['stkCallback']['ResultCode'];
        $ResultDesc = $data['Body']['stkCallback']['ResultDesc'];
        $TransactionDate = $data['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value'];
        $Amount = $data['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
        $MpesaReceiptNumber = $data['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
        $PhoneNumber = $data['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

        //if the amount paid matches the orderamount required
        if ($Amount == $order->totalamount) {
            $order->orderstatus = 'intransit';
            $order->paymentstatus = 'paid';
            $order->save();
        }
        //save the transaction
        Transactions::create([
            'MerchantRequestID' => $MerchantRequestID,
            'CheckoutRequestID' => $CheckoutRequestID,
            'ResultCode' => $ResultCode,
            'ResultDesc' => $ResultDesc,
            'Amount' => $Amount,
            'MpesaReceiptNumber' => $MpesaReceiptNumber,
            'TransactionDate' => $TransactionDate,
            'PhoneNumber' => $PhoneNumber
        ]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Confirmation received successfully']);
    }
}
