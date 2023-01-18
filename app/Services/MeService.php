<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MeService extends Service
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
     * For Get self identity
     *
     * @return Object
     */
    public function me()
    {
        return $this->detail(Auth::user()->id);
    }

    /**
     * For Update Password User
     *
     * @return bool
     */
    public function changePassword(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $user->password = $request->input('password');
        $user->save();

        return $this->detail($user->id);
    }

    public function upgradeAsContributor()
    {
        $user = User::findOrFail(Auth::user()->id);

        if ($user->is_contributor) {
            throw ValidationException::withMessages(['You are Contributor']);
        }

        if (!$user->is_customer) {
            throw ValidationException::withMessages(['Only customer can upgrade Contributor']);
        }

        $waitingContributor = $user->roles()->where('role_id', config('role.contributor'))->where(
            'status',
            UserRole::STATUS_PENDING
        )->exists();

        if ($waitingContributor) {
            throw ValidationException::withMessages(['You upgraded Contributor still waiting approval Admin']);
        }

        $rejectContributor = $user->roles()->where('role_id', config('role.contributor'))->where(
            'status',
            UserRole::STATUS_REJECTED
        )->exists();

        if ($rejectContributor) {
            throw ValidationException::withMessages(['You upgraded Contributor rejected by Admin']);
        }

        $user->roles()->create([
            'role_id' => config('role.contributor'),
            'status' => UserRole::STATUS_PENDING,
        ]);

        return $this->detail($user->id);
    }

    public function deactivate()
    {
        $user = User::findOrFail(Auth::user()->id);
        $user->is_active = false;
        $user->deactivate_at = now();
        $user->save();

        //Logout
        Auth::logout();

        return $this->detail($user->id);
    }

    public function changeProfile(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        $user->save();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'about_me' => $request->input('about_me'),
                'skills' => $user->is_contributor ? $request->input('skills') : null,
                'printer_tools' => $user->is_contributor ? $request->input('printer_tools') : null,
                'software_tools' => $user->is_contributor ? $request->input('software_tools') : null,
            ]
        );

        return $this->detail($user->id);
    }

    public function changePhoto(Request $request)
    {
        $user = User::findOrFail(Auth::user()->id);
        $user->photo = $this->uploadImage($request->input('photo'), User::FOLDER_NAME);
        $user->save();

        return $this->detail($user->id);
    }

    /**
     * For Validate Register
     *
     * @param array $request
     * @return \Illuminate\Validation\Validator
     */
    public function validationChangePassword(array $request)
    {
        $user = User::findOrFail(Auth::user()->id);

        return $this->validation($request, $this->rulesUpdatePassword($user->password));
    }

    public function validationChangeProfile(array $request)
    {
        return $this->validation($request, [
            'name' => 'required',
            'phone' => [
                'required',
                'regex:' . self::REGEX_PHONE,
                'min:10',
                'unique:users,phone,' . Auth::user()->id,
            ],
            'about_me' => 'required',
            'skills' => 'nullable|array',
            'skills.*' => 'required',
            'printer_tools' => 'nullable|array',
            'printer_tools.*' => 'required|uuid|exists:products,id',
            'software_tools' => 'nullable|array',
            'software_tools.*' => 'required',
        ]);
    }

    public function validationChangePhoto(array $request)
    {
        return $this->validation($request, [
            'photo' => 'required|base64image|base64imageSize:300',
        ]);
    }

    /**
     * For Filter Data
     *
     * @return Object
     */
    public function filter($model, $request)
    {
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
     * For Get Rules Register
     *
     * @return Array
     */
    private function rulesUpdatePassword($oldPassword)
    {
        Validator::extend(
            'old_password',
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            fn ($attribute, $value, $parameters, $validator) => Hash::check($value, $parameters[0])
        );

        //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        Validator::extend(
            'new_same_old_password',
            //phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
            fn ($attribute, $value, $parameters, $validator) => !Hash::check($value, $parameters[0])
        );

        return [
            //phpcs:ignore
            'password' => 'required|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|new_same_old_password:' . $oldPassword,
            'password_confirmation' => 'required',
            'old_password' => 'required|old_password:' . $oldPassword,
        ];
    }
}
