<?php

namespace App\Http\Controllers;

use App\Rules\IsValidPassword;
use App\User;
use Carbon\Carbon;
use Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mail;

class ForgotPasswordController extends Controller
{
    public function index()
    {
        return view('account.forgotpassword');
    }

    public function sendmail(Request $request)
    {
        $request->validate([
              'email' => 'required|email|exists:users',
          ]);

        $token = Str::random(64);

        DB::table('password_resets')->insert([
              'email' => $request->email,
              'token' => $token,
              'created_at' => Carbon::now(),
            ]);

        Mail::send('email.forgetPassword', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Password');
        });

        return back()->with('success', 'We have e-mailed your password reset link!');
    }

    public function showResetPasswordForm($token)
    {
        return view('login.form', ['token' => $token]);
    }

    public function submitResetPasswordForm(Request $request)
    {
        $request->validate([
              'email' => 'required|email|exists:users',
              'password' =>['min:8', 'max:64', 'required_with:password_confirmation', 'same:password_confirmation', new IsValidPassword()],
          ]);

        $updatePassword = DB::table('password_resets')
                              ->where([
                                'email' => $request->email,
                                'token' => $request->token,
                              ])
                              ->first();

        if (!$updatePassword) {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = User::where('email', $request->email)
                      ->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email'=> $request->email])->delete();

        return redirect('/login')->with('success', 'Your password has been updated successfully!');
    }
}
