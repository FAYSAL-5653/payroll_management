<?php
namespace App\Http\Controllers;

use App\Helper\JWTToken;
use App\Mail\OTPmail;
use App\Models\User;
use Exception;
use id;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Summary of UserRegistration
     * @param \Illuminate\Http\Request $request
     * @return mixed
     */

    //page call part
    public function LoginPage(): View
    {
        return view('pages.auth.login-page');
    }

    public function RegistrationPage(): View
    {
        return view('pages.auth.registration-page');
    }
    public function SendOtpPage(): View
    {
        return view('pages.auth.send-otp-page');
    }
    public function VerifyOTPPage(): View
    {
        return view('pages.auth.verify-otp-page');
    }

    public function ResetPasswordPage(): View
    {
        return view('pages.auth.reset-pass-page');
    }

    public function ProfilePage(): View
    {
        return view('pages.dashboard.profile-page');
    }

    //logic part
    public function UserRegistration(Request $request)
    {
        try {
            User::create([
                'firstName' => $request->input('firstName'),
                'lastName' => $request->input('lastName'),
                'email' => $request->input('email'),
                'mobile' => $request->input('mobile'),
                'password' => $request->input('password'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User Registration Successfully',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User Registration Failed',
            ]);
        }
    }

    public function UserLogin(Request $request)
    {
        $count = User::where('email', '=', $request->input('email'))
            ->where('password', '=', $request->input('password'))
            ->select('id')->first();

        if ($count !== null) {

            $token = JWTToken::CreateToken($request->input('email'), $count->id);
            return response()->json([
                'status' => 'success',
                'message' => 'User Login Successful',

            ])->cookie('token', $token, 60 * 24 * 30);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized',
            ]);

        }

    }
    public function SendOTPCode(Request $request)
    {

        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();

        if ($count == 1) {
            // OTP Email Address
            Mail::to($email)->send(new OTPMail($otp));
            // OTO Code Table Update
            User::where('email', '=', $email)->update(['otp' => $otp]);

            return response()->json([
                'status' => 'success',
                'message' => '4 Digit OTP Code has been send to your email !',
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized',
            ]);
        }
    }

    public function VerifyOTPCode(Request $request)
    {

        $email = $request->input('email');
        $otp = $request->input('otp');
        $count = User::where('email', '=', $email)->
            where('otp', '=', $otp)->count();

        if ($count == 1) {
            // OTO Code Table Update
            User::where('email', '=', $email)->update(['otp' => $otp]);

            // password reset token issu
            $token = JWTToken::CreateTokenForSetPassword($request->input('email'));

            return response()->json([
                'status' => 'success',
                'message' => ' OTP Code has been verified sucessfully !',

            ])->cookie('token', $token, 60 * 24 * 30);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized',
            ]);
        }
    }

    public function ResetPassword(Request $request)
    {
        try {
            $email = $request->header('email');
            $password = $request->input('password');
            User::where('email', '=', $email)->update(['password' => $password]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful',
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Something Went Wrong',
            ], 200);
        }
    }

    public function UserLogout()
    {
        return redirect('/userLogin')->cookie('token', '', -1);
    }

    public function UserProfile(Request $request)
    {
        $email = $request->header('email');
        $user = User::where('email', '=', $email)->first();
        return response()->json([
            'status' => 'success',
            'message' => 'Request Successful',
            'data' => $user,
        ], 200);

    }
    public function UpdateProfile(Request $request)
    {
        try {
            $email = $request->header('email');
            $firstName = $request->input('firstName');
            $lastName = $request->input('lastName');
            $mobile = $request->input('mobile');
            $password = $request->input('password');
            User::where('email', '=', $email)->update([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'mobile' => $mobile,
                'password' => $password,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful',
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Something Went Wrong',
            ], 200);
        }
    }

}
