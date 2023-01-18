<?php

namespace App\Http\Controllers;

use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    private $roleService;

    /**
     * Controller constructor.
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->roleService = new RoleService;
    }

    /**
     * Get all records
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $result = $this->roleService->listPaginateRole($this->request);

        return $this->respond($result);
    }

     /**
     * Show the record
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $result = $this->roleService->detailRole($id);

        return $this->respond($result);
    }
}
