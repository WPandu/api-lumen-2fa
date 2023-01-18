<?php

namespace App\Http\Controllers;

use App\Services\MeService;
use Illuminate\Http\Request;

class MeController extends Controller
{
    private $meService;

    /**
     * Controller constructor.
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->meService = new MeService;
    }

    /**
     * Show the self identity
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $result = $this->meService->me();

        return $this->respond($result);
    }

    /**
     * Update Password User
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword()
    {
        $this->meService->validationChangePassword($this->request->all())->validate();

        $result = $this->meService->changePassword($this->request);

        if (! $result) {
            $this->respondFailed(trans('error.change_failed'));
        }

        return $this->respondUpdated();
    }

    public function upgradeAsContributor()
    {
        $result = $this->meService->upgradeAsContributor();

        return $this->respondUpdated($result);
    }

    public function deactivate()
    {
        $result = $this->meService->deactivate();

        return $this->respondUpdated($result);
    }

    public function changeProfile()
    {
        $this->meService->validationChangeProfile($this->request->all())->validate();

        $result = $this->meService->changeProfile($this->request);

        return $this->respondUpdated($result);
    }

    public function changePhoto()
    {
        $this->meService->validationChangePhoto($this->request->all())->validate();

        $result = $this->meService->changePhoto($this->request);

        return $this->respondUpdated($result);
    }
}
