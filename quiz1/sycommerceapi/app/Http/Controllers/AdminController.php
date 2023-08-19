<?php

namespace App\Http\Controllers;

use App\Http\Utility\Common;
use App\Models\Activitylogs;
use App\Models\Orders;
use App\Models\Products;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    //fetch users
    public function Fetchusers(Request $request)
    {
        $user = $request->user();
        $allusers = User::where('id', '!=', $user->id)->with("orders")->get(); //get all users except super admin

        if (count($allusers) > 0) {
            return $allusers;
        } else {
            return Common::Returnerror("No users at the moment");
        }
    }

    //fetch all orders
    public function Fetchallorders(Request $request)
    {
        $allorders = Orders::all();

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

    //fetch activity logs
    public function Fetchactivitylogs(Request $request)
    {
        //fetchactovity logs with user
        $allactivitylogs = Activitylogs::with("user")->get();

        if (count($allactivitylogs) > 0) {
            return $allactivitylogs;
        } else {
            return Common::Returnerror("No logs at the moment");
        }
    }

    //change user activation status
    public function Changeactivationstatus(Request $request)
    {
        $userid = $request->userid;
        $user = User::find($userid);
        if ($user) {
            //if user has been activated change to inactivated and vise versa
            $newStatus = $user->activatedstatus == 1 ? 0 : 1;
            $user->update(['activatedstatus' => $newStatus]);

            return Common::Returnsuccess("Updated successfully");
        } else {
            return Common::Returnerror("User not found");
        }
    }

    //Post products
    public function Postproducts(Request $request)
    {
        $products = json_decode($request->input('products'), true);

        if (count($products) > 0) {
            $baseurl = $request->baseurl;
            foreach ($products as $productData) {
                $name = $productData['name'];
                $description = $productData['description'];
                $category = $productData['category'];
                $price = $productData['price'];
                $quantity = $productData['quantity'];

                // Store images and get image URLs
                $imageUrls = [];
                foreach ($productData['images'] as $image) {
                    $imageName = time() . '_' . $image->getClientOriginalName();
                    $image->storeAs('public/assets', $imageName);
                    $imageUrls[] = $baseurl + Storage::url('assets/' . $imageName);
                }

                // Create product record with image URLs
                $product = new Products();
                $product->name = $name;
                $product->description = $description;
                $product->category = $category;
                $product->price = $price;
                $product->quantity = $quantity;
                $product->imageurls = json_encode($imageUrls);
                $product->save();
            }

            return Common::Returnsuccess("Products posted successfully");
        } else {
            return Common::Returnerror("No products received");
        }
    }

    //Delete products
    public function Deleteproducts(Request $request)
    {
        $productIds = $request->productids;

        // Fetch product image URLs before deleting products
        $products = Products::whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            // Delete associated images
            $imageUrls = json_decode($product->imageurls, true);
            foreach ($imageUrls as $imageUrl) {
                $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
                $publicGalleryPath = public_path('assets');
                $filePath = $publicGalleryPath . '/' . $filename;

                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }
        }

        // Delete products
        Products::whereIn('id', $productIds)->delete();

        return Common::Returnsuccess("Deleted successfully");
    }
}
