<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ReCaptcha implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function passes($attribute, $value)
    {
        return true;
    }

    public function rules()
    {
        return [
             'name' => 'required|max: 255',
             'email' => 'required|email|max: 255',
             'message' => 'required',
             'g-recaptcha-response' => 'required|recaptcha',
         ];
    }

    public function message()
    {
        return [
       'required' => 'The :attribute field is required.',
       'email' => 'The :attribute must use a valid email address',
       'g-recaptcha-response.recaptcha' => 'Captcha verification failed',
       'g-recaptcha-response.required' => 'Please complete the captcha',
       ];
    }
}
