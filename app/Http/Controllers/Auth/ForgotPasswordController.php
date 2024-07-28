<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        if (env('MAIL_STATUS') == 'ACTIVE' && env('MAIL_USERNAME')) {
            $this->validateEmail($request);
            $response = $this->broker()->sendResetLink(
                $this->credentials($request)
            );

            if ($response == Password::RESET_LINK_SENT) {
                $this->sendSms($request->input('email')); // Call the SMS sending function
                return $this->sendResetLinkResponse($request, $response);
            } else {
                return $this->sendResetLinkFailedResponse($request, $response);
            }
        } else {
            return redirect()->back()->with('error', 'Mail credentials are off now. Please try again later');
        }
    }

    private function sendSms($email)
    {
        $phone = $this->getPhoneNumberByEmail($email); 
        if (!$phone) {
            Log::error('Phone number not found for email: ' . $email);
            return;
        }

        $message = [
            "secret" => env('SMS_API_SECRET'),
            "mode" => "devices",
            "device" => env('SMS_API_DEVICE_ID'),
            "sim" => 1,
            "priority" => 1,
            "phone" => $phone,
            "message" => "A password reset request has been received for your account."
        ];

        $cURL = curl_init("https://sms.olisd.com/api/send/sms");
        curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cURL, CURLOPT_POSTFIELDS, $message);
        $response = curl_exec($cURL);
        curl_close($cURL);

        $result = json_decode($response, true);

        if ($result['status'] == 200) {
            Log::info('SMS sent successfully.');
        } else {
            Log::error('Failed to send SMS: ' . $result['message']);
        }
    }

    private function getPhoneNumberByEmail($email)
    {
        
        return '+25191199672'; 
    }
}
