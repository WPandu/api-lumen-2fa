<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $authService;

    /**
     * Controller constructor.
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->authService = new AuthService;
    }

    /**
     * Login User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $this->authService->validationLogin($this->request->all())->validate();
        $result = $this->authService->login($this->request);

        if (!$result['access_token']) {
            return $this->setStatusCode(401)->respond([
                'errors' => [
                    $result['message'],
                ],
            ]);
        }

        return $this->respond([
            'data' => $result,
        ]);
    }

    /**
     * Logout User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->authService->logout($this->request);

        return $this->respond(['message' => 'success']);
    }

    /**
     * Forgot Password User
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail()
    {
        $this->authService->validationResetLink($this->request->all())->validate();

        $result = $this->authService->sendResetLinkEmail($this->request->input('email'));

        if (! $result) {
            return $this->respondFailValidation(trans('validation.email_not_found'));
        }

        return $this->respond(['message' => 'success']);
    }

    /**
     * Reset user password
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword()
    {
        $this->authService->validationResetPassword($this->request->all())->validate();

        $result = $this->authService->resetPassword($this->request);

        if (! $result) {
            return $this->respondFailValidation(trans('validation.invalid_token'));
        }

        return $this->respond(['message' => 'success']);
    }

    public function recaptcha()
    {
        return view('recaptcha.index');
    }

    public function twoFA()
    {
        return view('2fa.index');
    }

    public function googleBarcode()
    {
        $result = $this->authService->googleBarcode();

        return $this->respond([
            'data' => $result,
        ]);
    }

    public function registerGoogleBarcode()
    {
        $this->authService->validateGoogleBarcode($this->request->all());

        $this->authService->registerGoogleBarcode($this->request);

        return $this->respondUpdated();
    }
}
