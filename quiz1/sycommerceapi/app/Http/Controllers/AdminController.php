<?php

namespace App\Http\Controllers;

use App\Http\Utility\Common;
use App\Models\Activitylogs;
use App\Models\Orders;
use App\Models\Products;
use App\Models\Sitevisits;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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
        $products = $request->products;

        if (count($products) > 0) {
            $baseurl = $request->baseurl;
            foreach ($products as $productData) {
                $name = $productData['productName'];
                $description = $productData['productDescription'];
                $category = $productData['productCategory'];
                $price = $productData['productPrice'];
                $quantity = $productData['productQuantity'];

                // Store images and get image URLs
                $imageUrls = [];
                foreach ($productData['productImages'] as $imageData) {
                    $imagestring = $imageData['imagestring'];

                    // Remove the Data URI prefix
                    $base64Data = substr($imagestring, strpos($imagestring, ',') + 1);

                    $imageData = base64_decode($base64Data);

                    // Generate a unique image name
                    $imageName = time() . '_' . uniqid() . '.png'; // You can adjust the file extension as needed

                    // Define the public path to the assets directory
                    $publicAssetsPath = public_path('assets');

                    // Ensure the directory exists, create if not
                    if (!file_exists($publicAssetsPath)) {
                        mkdir($publicAssetsPath, 0777, true);
                    }

                    // Save the decoded image data as a file
                    $imagePath = $publicAssetsPath . '/' . $imageName;

                    // Use file_put_contents to create the image file
                    $success = file_put_contents($imagePath, $imageData);

                    if ($success !== false) {
                        // Construct the image URL
                        $imageUrl = $baseurl . 'assets/' . $imageName;
                        $imageUrls[] = $imageUrl;
                    } else {
                        // Handle the case where the image creation failed
                        // You might want to log an error message or take appropriate action
                    }
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

    //fetch site sitevisits
    public function Fetchsitevisits(Request $request)
    {
        $sitevisits = Sitevisits::all();

        if (count($sitevisits) > 0) {
            return $sitevisits;
        } else {
            return Common::Returnerror("No site visits yet");
        }
    }
}
