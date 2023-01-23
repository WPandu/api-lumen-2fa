<?php

namespace App\Services;

use App\Jobs\SendResetPasswordEmail;
use App\Models\User;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use PragmaRX\Google2FA\Google2FA;

class AuthService
{
    private $regexPassword = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';

    /**
     * For Validate Login
     *
     * @param array $params
     * @return \Illuminate\Validation\Validator
     */
    public function validationLogin(array $params)
    {
        return $this->validation($params, $this->rulesLogin(), [
            'password.required' => trans('validation.password_required'),
        ]);
    }

    /**
     * For Login
     *
     * @param array $params
     * @return Array
     */
    public function login(Request $request)
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $user = User::where('email', $email)->first();
        $otp = $request->input('otp');

        if (is_null($user)) {
            return [
                'error_code' => 'EMAIL_NOT_FOUND',
                'message' => trans('validation.email_not_found'),
                'access_token' => null,
            ];
        }

        $token = Auth::attempt([
            'email' => $email,
            'password' => $password,
        ]);

        if (! $token) {
            return [
                'error_code' => 'WRONG_EMAIL_PASSWORD',
                'message' => trans('validation.wrong_email_password'),
                'access_token' => null,
            ];
        }

        if (!$user->is_active) {
            return [
                'error_code' => 'USER_INACTIVE',
                'message' => trans('validation.user_not_active'),
                'access_token' => null,
            ];
        }

        if ($user->google2fa_secret && !$otp) {
            JWTAuth::setToken($token)->invalidate();

            return [
                'error_code' => 'NEED_OTP',
                'message' => 'You must insert OTP',
                'access_token' => null,
            ];
        }

        //Logout Last Token
        if ($user->last_token) {
            try {
                JWTAuth::setToken($user->last_token)->invalidate();
            //phpcs:ignore
            } catch (TokenExpiredException $e) {
                $user->last_token = null;
            //phpcs:ignore
            } catch (TokenInvalidException $e) {
                $user->last_token = null;
            }
        }

        if ($user->google2fa_secret) {
            $google2fa = new Google2FA;
            $checkOTP = $google2fa->verifyKey($user->google2fa_secret, $otp);

            if (!$checkOTP) {
                return [
                    'error_code' => 'OTP_INVALID',
                    'message' => 'OTP Invalid',
                    'access_token' => null,
                ];
            }
        }

        //Update Last Token
        $user->last_token = $token;
        $user->save();

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::factory()->getTTL() * 60,
        ];
    }

    /**
     * For Logout
     */
    public function logout()
    {
        Auth::logout();
    }

    /**
     * For Validate Reset Link to Email
     *
     * @param array $params
     * @return \Illuminate\Validation\Validator
     */
    public function validationResetLink(array $params)
    {
        return $this->validation($params, ['email' => 'required|email']);
    }

    public function sendResetLinkEmail($email)
    {
        try {
            $user = User::where('email', $email)->first();

            if (! $user) {
                return false;
            }

            $user->reset_token = Str::random(32);
            $user->save();

            //Send Email Reset Password
            dispatch(new SendResetPasswordEmail($user));
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * For Validate Reset Password
     *
     * @param array $params
     * @return \Illuminate\Validation\Validator
     */
    public function validationResetPassword(array $params)
    {
        $validator = $this->validation($params, $this->rulesResetPassword());
        $validator->after(function ($validator) use ($params) {
            if ($params['password'] !== $params['password_confirmation']) {
                $validator->errors()->add('password', 'Password and password confirmation does not match');
            }
        });

        return $validator;
    }

    public function validateGoogleBarcode(array $params)
    {
        return $this->validation($params, [
            'google2fa_secret' => 'required',
        ]);
    }

    /**
     * Forgot Password
     *
     * @param $email Email user
     * @return object
     */
    public function resetPassword(Request $request)
    {
        $user = User::where('email', $request->input('email'))
            ->where('reset_token', $request->input('token'))->first();

        if (!$user) {
            return false;
        }

        $user->password = $request->input('password');
        $user->reset_token = null;

        return $user->save();
    }

    public function googleBarcode()
    {
        $google2fa = new Google2FA;
        $secretKey = $google2fa->generateSecretKey(32);
        $g2faUrl = $google2fa->getQRCodeUrl(
            env('APP_NAME'),
            Auth::user()->email,
            $secretKey
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(300),
                new ImagickImageBackEnd
            )
        );

        $qrcodeImage = base64_encode($writer->writeString($g2faUrl));

        return [
            'qr_code' => $qrcodeImage,
            'secret_key' => $secretKey,
        ];
    }

    public function registerGoogleBarcode(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $user->google2fa_secret = $request->input('google2fa_secret');
        $user->save();
    }

    /**
     * For Validation param
     *
     * @param $rules
     * @return \Illuminate\Validation\Validator
     */
    private function validation(array $request, array $rules, array $messages = [])
    {
        return Validator::make($request, $rules, $messages);
    }

    /**
     * For Get Rules Login
     *
     * @return Array
     */
    private function rulesLogin()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'recaptcha' => app()->environment('local') ? '' : 'required|recaptcha',
            'otp' => 'nullable|numeric|digits:6',
        ];
    }

    private function rulesResetPassword()
    {
        return [
            'email' => 'required|email',
            'token' => 'required',
            //phpcs:ignore
            'password' => "required|min:8|regex:{$this->regexPassword}",
            'password_confirmation' => 'required',
        ];
    }
}
