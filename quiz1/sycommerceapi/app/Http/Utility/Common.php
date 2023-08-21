<?php

namespace App\Http\Utility;

use App\Models\Messages;
use Exception as GlobalException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\PHPMailer;


class Common
{
    //return error message
    public static function Returnerror($error)
    {
        $response = array();
        $response["error"] = true;
        $response["message"] = $error;
        return json_encode($response);
    }

    //function to respond with a success messsage
    public static function Returnsuccess($message)
    {
        $response = array();
        $response["error"] = false;
        $response["success"] = $message;
        return json_encode($response);
    }


    //function to send register user confirmation code 
    public static function Sendregistercode($recipient, $code)
    {
        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions

        try {

            // Email server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');             //  smtp host
            $mail->SMTPAuth = true;
            $mail->Username = env('NOREPLY_EMAIL');   //  sender username
            $mail->Password = env('NOREPLY_PASSWORD');       // sender password
            // $mail->SMTPSecure = 'tls';                  // encryption - ssl/tls
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;                          // port - 587/465

            $mail->setFrom(env('NOREPLY_EMAIL'), 'Sycommerce noreply');
            $mail->addAddress($recipient);
            $mail->isHTML(true);                // Set email content format to HTML

            $mail->Subject = $code . " is your Sycommerce verification code";
            $mail->Body =
                "<div><H4>Confirm your email address</H4>
            <p>
                There's one quick step you need to complete in order <br>
                to confirm your email address.
            </p>
            <p>
                Please enter this verification code on Rightroute app <br>
                when prompted
            </p>
            <h2>" . $code . "</h2>
            <p>
                Verification code exprires after two hours.
            </p>
            <div>
                Thanks<br>
                Sycommerce Team
            </div>
             </div>";

            // $mail->AltBody = plain text version of email body;

            if (!$mail->send()) {
                return false;
            } else {
                return true;
            }
        } catch (GlobalException $e) {
            return false;
        }
    }



    //send resetpass email
    public static function Sendresetpassemail($recipient, $code)
    {
        require base_path("vendor/autoload.php");
        $mail = new PHPMailer(true);     // Passing `true` enables exceptions

        try {

            // Email server settings
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = env('MAIL_HOST');             //  smtp host
            $mail->SMTPAuth = true;
            $mail->Username = env('PASSWORDRESET_EMAIL');   //  sender username
            $mail->Password = env('PASSWORDRESET_PASSWORD');       // sender password
            // $mail->SMTPSecure = 'tls';                  // encryption - ssl/tls
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;                          // port - 587/465

            $mail->setFrom(env('PASSWORDRESET_EMAIL'), 'Password Reset!!');
            $mail->addAddress($recipient);
            $mail->isHTML(true);                // Set email content format to HTML

            $mail->Subject = $code . " is your Sycommerce resetpass code";
            $mail->Body =
                "<div>
            <p>
                We received a password reset request.The code to reset you password is below,if you did not make
                this request, you can ignore this email
            </p><br>
            <font color='red'>*Note</font> the code expires after 30 minutes since the reset password request was made
            <p>Here is your password reset code: <br>
                " . $code . "</p>
            <hr style='width:100%;text-align:center;margin-left:5;color:red;background-color:red'>
            <p>This is an automated email.Please do not reply to this email</p>
            <div>
                Thanks<br>
                Sycommerce Team
            </div>
        </div>";

            if (!$mail->send()) {
                return false;
            } else {
                return true;
            }
        } catch (GlobalException $e) {
            return false;
        }
    }

    //send stk push
    public static function Sendstkpush($amount, $phoneNumber, $orderid)
    {

        $combined = env('DARAJA_CONSUMER_KEY') . ':' . env('DARAJA_CONSUMER_SECRET');
        Log::info($combined);
        $credentials = base64_encode($combined);
        $accessToken = Http::withHeaders(['Authorization' => 'Basic ' . $credentials])
            ->get('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials')
            ->json('access_token');

        $mpesaOnlineShortcode = "174379";
        $BusinessShortCode = $mpesaOnlineShortcode;
        $mpesaOnlinePasskey = env('MPESAONLINEPASS');
        date_default_timezone_set('Africa/Nairobi');
        $timestamp =  date('YmdHis');
        $dataToEncode = $BusinessShortCode . $mpesaOnlinePasskey . $timestamp;
        $password = base64_encode($dataToEncode);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $accessToken
        ])->post('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest', [
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phoneNumber,
            'PartyB' => $BusinessShortCode,
            'PhoneNumber' => $phoneNumber,
            'CallBackURL' => 'https://apisycommerce.theiplug.com/apis/buyers/completetransaction?token=sycommercetest?orderid=' . $orderid,
            'AccountReference' => 'SYCOMMERCE',
            'TransactionDesc' => 'PAYING ORDER AMOUNT FOR SYMACOMMERCE'
        ])->json();
        Log::info($response);

        return $response['ResponseCode'] === 0;
    }
}
