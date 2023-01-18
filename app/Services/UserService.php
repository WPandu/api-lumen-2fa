<?php

namespace App\Services;

use App\Events\UserActived;
use App\Events\UserRegistered;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Auth;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService extends Service
{
    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(new User);
    }

    /**
     * For Get ALL from DB
     *
     * @return \App\Services\Collection
     */
    public function listPaginateUser(Request $request)
    {
        return $this->listPaginate($request);
    }

    /**
     * For Insert to DB
     *
     * @return bool
     */
    public function insertUser(Request $request)
    {
        try {
            $id = null;

            DB::transaction(function () use (&$id, $request) {
                $user = $this->insert($request->all());

                $id = $user->id;
                $roles = config('role');

                //Insert Role
                foreach ($request->input('role_ids') ?: [] as $roleId) {
                    $user->roles()->create([
                        'role_id' => $roleId,
                        'status' => $roleId === $roles[Role::ROLE_CONTRIBUTOR] ? UserRole::STATUS_APPROVED : null,
                        'answered_by' => $roleId === $roles[Role::ROLE_CONTRIBUTOR] ? Auth::user()->id : null,
                        'answered_at' => $roleId === $roles[Role::ROLE_CONTRIBUTOR] ? now() : null,
                    ]);
                }
            });
        } catch (Exception $e) {
            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return $this->detail($id);
    }

    /**
     * For Update to DB
     *
     * @return bool
     */
    public function updateUser($id, Request $request)
    {
        try {
            DB::transaction(function () use ($id, $request) {
                $user = $this->update($id, $request->all());
                //Update Roles
                $roles = $user->roles()->get()->pluck('role_id')->toArray();

                foreach ($request->input('role_ids') ?: [] as $roleId) {
                    $keyRole = array_search($roleId, $roles, true);

                    if (is_numeric($keyRole)) {
                        unset($roles[$keyRole]);
                    }

                    if ($user->roles()->where('role_id', $roleId)->exists()) {
                        continue;
                    }

                    $user->roles()->create([
                        'role_id' => $roleId,
                    ]);
                }

                if (count($roles) <= 0) {
                    return;
                }

                UserRole::where('user_id', $id)
                    ->whereIn('role_id', $roles)
                    ->delete();
            });
        } catch (Exception $e) {
            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return $this->detail($id);
    }

    /**
     * For Get Detail from DB
     *
     * @return Object
     */
    public function detailUser($id)
    {
        return $this->detail($id);
    }

    /**
     * For Get Detail from DB
     *
     * @return Object
     */
    public function deleteUser($id)
    {
        $result = $this->detail($id);
        $this->delete($id);

        return $result;
    }

    public function registerUser(Request $request)
    {
        try {
            $id = null;

            DB::transaction(function () use (&$id, $request) {
                $user = $this->insert([
                    'email' => $request->input('email'),
                    'password' => $request->input('password'),
                    'name' => $request->input('name'),
                    'phone' => $request->input('phone'),
                    'activation_token' => Str::random(50),
                    'is_active' => false,
                ]);

                $user->addresses()->create([
                    'address_name' => '-',
                    'address_phone' => $request->input('phone'),
                    'address' => $request->input('address'),
                    'is_primary' => true,
                    'sequence' => 1,
                ]);

                $id = $user->id;
                $roles = config('role');

                //Insert Role
                foreach ($request->input('role_ids') ?: [] as $roleId) {
                    if ($roleId !== $roles[Role::ROLE_CUSTOMER] && $roleId !== $roles[Role::ROLE_CONTRIBUTOR]) {
                        throw ValidationException::withMessages(['Role ID ' . $roleId . ' invalid']);
                    }

                    $user->roles()->create([
                        'role_id' => $roleId,
                        'status' => $roleId === $roles[Role::ROLE_CONTRIBUTOR] ? UserRole::STATUS_PENDING : null,
                    ]);
                }
            });

            //Send Email Verification
            event(new UserRegistered(User::findOrFail($id)));
        } catch (Exception $e) {
            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return $this->detail($id);
    }

    public function activationUser(Request $request)
    {
        try {
            $user = User::where('email', $request->input('email'))
                ->where('activation_token', $request->input('activation_token'))
                ->first();

            if (is_null($user)) {
                throw ValidationException::withMessages(['Email or Token Invalid']);
            }

            $user->activation_token = null;
            $user->is_active = true;
            $user->active_at = now();
            $user->save();

            event(new UserActived($user->refresh()));
        } catch (Exception $e) {
            throw ValidationException::withMessages([$e->getMessage()]);
        }

        return $this->detail($user->id);
    }

    public function approvalUser($id, Request $request)
    {
        $user = User::findOrFail($id);
        $userRole = $user->roles()->where('role_id', $request->input('role_id'))->firstOrFail();
        $userRole->status = $request->input('answer');
        $userRole->reject_reason = $request->input('answer') === UserRole::STATUS_REJECTED ? $request->input(
            'reject_reason'
        ) : null;
        $userRole->answered_by = Auth::user()->id;
        $userRole->answered_at = now();
        $userRole->save();

        return $this->detail($id);
    }

    /**
     * For Filter Data
     *
     * @return Object
     */
    public function filter($model, $request)
    {
        if ($request->input('name')) {
            $model->where('name', 'ILIKE', '%' . $request->input('name') . '%');
        }

        if ($request->input('email')) {
            $model->where('email', 'ILIKE', '%' . $request->input('email') . '%');
        }

        if (is_numeric($request->input('is_active'))) {
            $model->where('is_active', $request->input('is_active'));
        }

        if ($request->input('is_contributor')) {
            $model->whereRelation('roles', 'role_id', config('role.contributor'));
        }

        if ($request->input('role_status')) {
            $model->whereRelation('roles', 'status', $request->input('role_status'));
        }

        return $model;
    }

    /**
     * For Sorting Data
     *
     * @return Object
     */
    //phpcs:ignore
    public function sorting($model, $request)
    {
        return $model->latest();
    }

    /**
     * For Get Detail from DB
     *
     * @return Object
     */
    public function validationUser(Request $request, $id = null)
    {
        if ($id) {
            $user = User::findOrFail($id);

            if ($user->deactivate_at) {
                throw ValidationException::withMessages(['This user self deactivated']);
            }
        }

        return $this->validation($request->all(), $this->rules($id));
    }

    public function validationRegisterUser(Request $request)
    {
        return $this->validation($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => [
                'required',
                'regex:' . self::REGEX_PHONE,
                'min:10',
                'unique:users,phone',
            ],
            'password' => 'required|min:8|confirmed|regex:' . self::REGEX_PASSWORD,
            'password_confirmation' => 'required',
            'role_ids' => 'required|array',
            'role_ids.*' => 'required|uuid|exists:roles,id',
            'address' => 'required',
            'recaptcha' => 'required|recaptcha',
        ]);
    }

    public function validationActivationUser(Request $request)
    {
        return $this->validation($request->all(), [
            'email' => 'required|email',
            'activation_token' => 'required',
        ]);
    }

    public function validationApprovalUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $checkUser = $user->roles()
            ->where('role_id', $request->input('role_id'))
            ->where('status', UserRole::STATUS_PENDING)
            ->exists();

        if (!$checkUser) {
            throw ValidationException::withMessages(['This Role was Answer']);
        }

        return $this->validation($request->all(), [
            'answer' => 'required|in:approved,rejected',
            'role_id' => 'required|uuid|exists:user_roles',
            'reject_reason' => 'nullable|required_if:answer,rejected',
        ]);
    }

    /**
     * For Get Rules
     *
     * @return Array
     */
    private function rules($id)
    {
        return [
            'name' => 'required',
            'email' => is_null(
                $id
            ) ? 'required|email|unique:users' : 'required|email|unique:users,email,' . $id . ',id',
            //phpcs:ignore
            'password' => is_null($id) ? 'required|min:8|confirmed|regex:' . self::REGEX_PASSWORD : 'nullable|min:8|regex:' . self::REGEX_PASSWORD,
            'password_confirmation' => is_null($id) ? 'required' : 'nullable',
            'is_active' => 'required|boolean',
            'role_ids' => 'required|array',
            'role_ids.*' => 'required|uuid|exists:roles,id',
        ];
    }
}
