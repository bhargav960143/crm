<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Models\Role;
use App\Models\User;
use App\Traits\Auditable;
use Illuminate\Http\Request;
use Exception;
use DB;
use Hash;
use App\Traits\Validation;
use Illuminate\Support\Facades\Validator;

class CompanyUserApiController extends Controller
{
    use Validation;

    public $successStatus = 200;
    /**
     * @api {get} /api/v1/company-user-list Company User List
     * @apiSampleRequest off
     * @apiName Company
     * @apiGroup Company User List
     * @apiVersion 1.0.0
     *
     * @apiDescription <span class="type type__post">Company User List API</span>
     *
     *   API request content-type [{"key":"Content-Type","value":"application/json"}]
     *
     *   Authorization is based on token shared while login
     *
     *   @apiHeader {String} authorization (Bearer Token) Authorization value.
     *
     *   @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer XXXXXXXXXX"
     *     }
     *
     *   @apiParam {integer}     [page]    Page No
     *
     *   Validate `page` is integer
     *
     * @apiParamExample {queryParam} Request-Example:
     *    {
     *           "page" : 2
     *    }
     *
     * @apiSuccess {Boolean}   status                               Response successful or not
     * @apiSuccess {Array}    data                                  Company User List
     *
     * @apiExample {curl} Example usage:
     *       curl -i https://crm.trentiums.com/api/v1/company-user-list
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      {
     *          "status": true,
     *          "data": {
     *              "current_page": 1,
     *              "data": [
     *              {
     *                  "id": 2,
     *                  "name": "Trentium Solution Private Limited",
     *                  "email": "info@trentiums.com",
     *                  "email_verified_at": null,
     *                  "user_role": 2,
     *                  "created_at": "2024-06-04 10:07:02"
     *                  "company_user_id": 1
     *              },
     *              {
     *                  "id": 3,
     *                  "name": "Nishita",
     *                  "email": "nishitap@trentiums.com",
     *                  "email_verified_at": null,
     *                  "user_role": 3,
     *                  "created_at": "2024-06-11 11:51:36"
     *                  "company_user_id": 2
     *              },
     *              {
     *                  "id": 5,
     *                  "name": "Test",
     *                  "email": "test@test.com",
     *                  "email_verified_at": "2024-06-14 06:17:06",
     *                  "user_role": 3,
     *                  "created_at": "2024-06-14 06:17:06"
     *                 "company_user_id": 3
     *              }
     *              ],
     *              "first_page_url": "https://crm.trentiums.com/api/v1/company-user-list?page=1",
     *              "from": null,
     *              "last_page": 1,
     *              "last_page_url": "https://crm.trentiums.com/api/v1/company-user-list?page=1",
     *              "links": [
     *                  {
     *                      "url": null,
     *                      "label": "&laquo; Previous",
     *                      "active": false
     *                  },
     *                  {
     *                      "url": "https://crm.trentiums.com/api/v1/company-user-list?page=1",
     *                      "label": "1",
     *                      "active": true
     *                  },
     *                  {
     *                      "url": null,
     *                      "label": "Next &raquo;",
     *                      "active": false
     *                  }
     *              ],
     *              "next_page_url": null,
     *              "path": "https://crm.trentiums.com/api/v1/company-user-list",
     *              "per_page": 10,
     *              "prev_page_url": null,
     *              "to": null,
     *              "total": 0
     *          }
     *      }
     *
     *     HTTP/1.1 200 Bad Request
     *     {
     *       "status": false,
     *       "message": "Something wen't wrong please try again"
     *     }
     */
    public function company_user_list(Request $request)
    {
        try {
            $userRequest = $request->all();
            $user = $request->user();
            if ($user->user_role == array_flip(Role::ROLES)['Company Admin']) {
                if (isset($user->company) && !empty($user->company)) {
                    $companyUser = CompanyUser::join('users', "users.id", "=", "company_users.user_id")
                        ->join('companies', "companies.id", "=", "company_users.company_id")
                        ->where("company_id", "=", $user->company->id)
                        ->select(['users.id', 'users.name', 'users.email', 'users.email_verified_at', 'users.user_role', 'users.created_at'])
                        ->paginate(10);
                    return response()->json(['status' => true, 'data' => $companyUser], $this->successStatus);
                } else {
                    Auditable::log_audit_data('CompanyUserApiController@company_user_list Company not found', null, config('settings.log_type')[1], $userRequest);
                    return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
                }
            } else {
                Auditable::log_audit_data('CompanyUserApiController@company_user_list staff can try to check api', null, config('settings.log_type')[1], $userRequest);
                return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
            }
        } catch (Exception $ex) {
            Auditable::log_audit_data('CompanyUserApiController@company_user_list Exception', null, config('settings.log_type')[0], $ex->getMessage());
            return response()->json(['status' => false, 'message' => trans('label.something_went_wrong_error_msg')], $this->successStatus);
        }
    }

    /**
     * @api {post} /api/v1/save-company-user Save Company User
     * @apiSampleRequest off
     * @apiName Save Company User
     * @apiGroup Company
     * @apiVersion 1.0.0
     *
     * @apiDescription <span class="type type__post">Save Company User API</span>
     *
     *   API request content-type [{"key":"Content-Type","value":"application/json"}]
     *
     *   Authorization is based on token shared while login
     *
     *   @apiHeader {String} authorization (Bearer Token) Authorization value.
     *
     *   @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer XXXXXXXXXX"
     *     }
     *
     * @apiParam {string}   name    Name
     *
     *    Validate `name` is required
     *
     *    Validate `name` is string
     *
     * @apiParam {String}     email       Email
     *
     *   Validate `email` field is required.
     *
     *   Validate `email` Length Minimum 2 characters.
     *
     *   Validate `email` with `rfc,dns` (Laravel Default email validation).
     *
     *   Validate `email` is exists or not
     *
     * @apiParam {String}     password        Security Password for account login
     *
     *   Validate `password` field is required.
     *
     *   Validate `password` is string
     *
     *   Validate `password` field must be minimum 8 character.
     *
     *   Validate `password` field should have at least 1 lowercase AND 1 uppercase AND 1 number.
     *
     * @apiParamExample {Json} Request-Example:
     *    {
     *    "name": "Bhargav",
     *    "email": "bhargav960143@gmail.com",
     *    "password": "Demo@123"
     *    }
     *
     * @apiSuccess {Boolean}   status                               Response successful or not
     * @apiSuccess {String}    message                              Message for error & success
     *
     * @apiExample {curl} Example usage:
     *       curl -i https://crm.trentiums.com/api/v1/save-company-user
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      {
     *          "status": true,
     *          "message": "Staff member saved successfully."
     *      }
     *
     *      {
     *          "status": false,
     *          "message": "Provided email already in use, please try again"
     *      }
     *
     *     HTTP/1.1 200 Bad Request
     *     {
     *          "status": false,
     *          "message": "Something wen't wrong please try again"
     *     }
     */
    public function save_company_user(Request $request)
    {
        try {
            $userRequest = $request->all();
            $user = $request->user();
            $fields['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ];
            $fields['email'] = [
                'required',
                'min:2',
                'email:rfc,dns',
                'unique:users,email',
            ];
            $fields['name'] = [
                'required',
                'string'
            ];

            $error = Validator::make($request->all(), $fields, [
                'email.required' => trans('label.email_required_error_msg'),
                'email.string' => trans('label.email_string_error_msg'),
                'email.email' => trans('label.email_format_error_msg'),
                'email.unique' => trans('label.email_unique_error_msg'),
                'password.required' => trans('label.password_required_error_msg'),
                'password.string' => trans('label.password_string_error_msg'),
                'password.min' => trans('label.password_min_error_msg'),
                'password.regex' => trans('label.password_regex_error_msg'),
                'name.required' => trans('label.company_user_name_required_error_msg'),
                'name.string' => trans('label.company_user_name_string_error_msg'),
            ]);
            DB::beginTransaction();
            $validationResponse = $this->check_validation($fields, $error, 'Company User Save');
            if (!$validationResponse->getData()->status) {
                return $validationResponse;
            }
            if ($user->user_role == array_flip(Role::ROLES)['Company Admin']) {
                if (isset($user->company) && !empty($user->company)) {
                    $userMain = new User();
                    $userMain->name = ucfirst($userRequest['name']);
                    $userMain->email = strtolower($userRequest['email']);
                    $userMain->password = Hash::make(trim($userRequest['password']));
                    $userMain->user_role = array_flip(Role::ROLES)['Company Staff'];
                    $userMain->created_at = date("Y-m-d H:i:s");
                    $userDetails = $userMain->save();
                    if ($userDetails) {
                        $userMain->roles()->sync(array_flip(Role::ROLES)['Company Staff']);
                        $companyUser = new CompanyUser();
                        $companyUser->company_id = $user->company->id;
                        $companyUser->user_id = $userMain->id;
                        $companyUser->created_at = date("Y-m-d H:i:s");
                        $companyUser->save();
                        DB::commit();
                        return response()->json(['status' => true, 'message' => trans('label.staff_user_created_success_msg')], $this->successStatus);
                    } else {
                        DB::rollback();
                        Auditable::log_audit_data('CompanyUserApiController@save_company_user User not created', null, config('settings.log_type')[1], $userRequest);
                        return response()->json(['status' => false, 'message' => trans('label.unable_to_create_user_error_msg')], $this->successStatus);
                    }
                } else {
                    DB::rollback();
                    Auditable::log_audit_data('CompanyUserApiController@save_company_user Company not found', null, config('settings.log_type')[1], $userRequest);
                    return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
                }
            } else {
                DB::rollback();
                Auditable::log_audit_data('CompanyUserApiController@save_company_user staff can try to check api', null, config('settings.log_type')[1], $userRequest);
                return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
            }
        } catch (Exception $ex) {
            DB::rollback();
            Auditable::log_audit_data('CompanyUserApiController@save_company_user Exception', null, config('settings.log_type')[0], $ex->getMessage());
            return response()->json(['status' => false, 'message' => trans('label.something_went_wrong_error_msg')], $this->successStatus);
        }
    }

    /**
     * @api {post} /api/v1/update-company-user Update Company User
     * @apiSampleRequest off
     * @apiName Update Company User
     * @apiGroup Company
     * @apiVersion 1.0.0
     *
     * @apiDescription <span class="type type__post">Update Company User API</span>
     *
     *   API request content-type [{"key":"Content-Type","value":"application/json"}]
     *
     *   Authorization is based on token shared while login
     *
     *   @apiHeader {String} authorization (Bearer Token) Authorization value.
     *
     *   @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer XXXXXXXXXX"
     *     }
     *
     *  @apiParam {Integer}     user_id     User Id
     *
     *    Validate `user_id` is required
     *
     *    Validate `user_id` is integer
     *
     *    Validate `user_id` is exists or not
     *
     * @apiParam {string}   name    Name
     *
     *    Validate `name` is required
     *
     *    Validate `name` is string
     *
     * @apiParam {String}     email       Email
     *
     *   Validate `email` field is required.
     *
     *   Validate `email` Length Minimum 2 characters.
     *
     *   Validate `email` with `rfc,dns` (Laravel Default email validation).
     *
     *   Validate `email` is exists or not
     *
     * @apiParam {String}     password        Security Password for account login
     *
     *   Validate `password` field is required.
     *
     *   Validate `password` is string
     *
     *   Validate `password` field must be minimum 8 character.
     *
     *   Validate `password` field should have at least 1 lowercase AND 1 uppercase AND 1 number.
     *
     * @apiParamExample {Json} Request-Example:
     *    {
     *    "user_id": 1,
     *    "name": "Bhargav",
     *    "email": "bhargav960143@gmail.com",
     *    "password": "Demo@123"
     *    }
     *
     * @apiSuccess {Boolean}   status                               Response successful or not
     * @apiSuccess {String}    message                              Message for error & success
     *
     * @apiExample {curl} Example usage:
     *       curl -i https://crm.trentiums.com/api/v1/update-company-user
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      {
     *          "status": true,
     *          "message": "Staff member updated successfully."
     *      }
     *
     *      {
     *          "status": false,
     *          "message": "Provided email already in use, please try again"
     *      }
     *
     *     HTTP/1.1 200 Bad Request
     *     {
     *          "status": false,
     *          "message": "Something wen't wrong please try again"
     *     }
     */
    public function update_company_user(Request $request)
    {
        try {
            $userRequest = $request->all();
            $user = $request->user();

            $fields['user_id'] = [
                'required',
                'integer',
                'exists:users,id,deleted_at,NULL'
            ];
            $fields['password'] = [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
            ];
            $fields['email'] = [
                'required',
                'min:2',
                'email:rfc,dns',
                'unique_email_except:' . $userRequest['user_id'],
            ];
            $fields['name'] = [
                'required',
                'string'
            ];

            $error = Validator::make($request->all(), $fields, [
                'user_id.required' => trans('label.user_id_required_error_msg'),
                'user_id.exists' => trans('label.user_id_exists_error_msg'),
                'user_id.integer' => trans('label.user_id_integer_error_msg'),
                'email.required' => trans('label.email_required_error_msg'),
                'email.string' => trans('label.email_string_error_msg'),
                'email.email' => trans('label.email_format_error_msg'),
                'email.unique_email_except' => trans('label.email_unique_error_msg'),
                'password.required' => trans('label.password_required_error_msg'),
                'password.string' => trans('label.password_string_error_msg'),
                'password.min' => trans('label.password_min_error_msg'),
                'password.regex' => trans('label.password_regex_error_msg'),
                'name.required' => trans('label.company_user_name_required_error_msg'),
                'name.string' => trans('label.company_user_name_string_error_msg'),
            ]);
            DB::beginTransaction();
            $validationResponse = $this->check_validation($fields, $error, 'Update Company');
            if (!$validationResponse->getData()->status) {
                return $validationResponse;
            }

            if ($user->user_role == array_flip(Role::ROLES)['Company Admin']) {
                if (isset($user->company) && !empty($user->company)) {
                    $userMain = User::find($userRequest['user_id']);
                    if ($user->company->id == $userMain->companyUser->company_id && $userMain->user_role != array_flip(Role::ROLES)['Company Admin']) {
                        $userMain->name = ucfirst($userRequest['name']);
                        $userMain->email = strtolower($userRequest['email']);
                        $userMain->password = Hash::make(trim($userRequest['password']));
                        $userMain->user_role = array_flip(Role::ROLES)['Company Staff'];
                        $userMain->updated_at = date("Y-m-d H:i:s");
                        $userMain->save();
                        DB::commit();
                        return response()->json(['status' => true, 'message' => trans('label.staff_user_updated_success_msg')], $this->successStatus);
                    } else {
                        DB::rollback();
                        Auditable::log_audit_data('CompanyUserApiController@update_company_user Cannot update company user', null, config('settings.log_type')[1], $userRequest);
                        return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
                    }
                } else {
                    DB::rollback();
                    Auditable::log_audit_data('CompanyUserApiController@update_company_user Company not found', null, config('settings.log_type')[1], $userRequest);
                    return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
                }
            } else {
                DB::rollback();
                Auditable::log_audit_data('CompanyUserApiController@update_company_user staff can try to check api', null, config('settings.log_type')[1], $userRequest);
                return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
            }
        } catch (Exception $ex) {
            DB::rollback();
            Auditable::log_audit_data('CompanyUserApiController@update_company_user Exception', null, config('settings.log_type')[0], $ex->getMessage());
            return response()->json(['status' => false, 'message' => trans('label.something_went_wrong_error_msg')], $this->successStatus);
        }
    }

    /**
     * @api {post} /api/v1/delete-company-user Delete Company User
     * @apiSampleRequest off
     * @apiName Delete Company User
     * @apiGroup Company
     * @apiVersion 1.0.0
     *
     * @apiDescription <span class="type type__post">Delete Company User API</span>
     *
     *   API request content-type [{"key":"Content-Type","value":"application/json"}]
     *
     *   Authorization is based on token shared while login
     *
     *   @apiHeader {String} authorization (Bearer Token) Authorization value.
     *
     *   @apiHeaderExample {json} Header-Example:
     *     {
     *       "Authorization": "Bearer XXXXXXXXXX"
     *     }
     *
     *  @apiParam {Integer}     user_id     User Id
     *
     *    Validate `user_id` is required
     *
     *    Validate `user_id` is integer
     *
     *    Validate `user_id` is exists or not
     *
     * @apiParamExample {Json} Request-Example:
     *    {
     *          "user_id": 1,
     *    }
     *
     * @apiSuccess {Boolean}   status                               Response successful or not
     * @apiSuccess {String}    message                              Message for error & success
     *
     * @apiExample {curl} Example usage:
     *       curl -i https://crm.trentiums.com/api/v1/delete-company-user
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *      {
     *          "status": true,
     *          "message": "Staff member deleted successfully."
     *      }
     *
     *     HTTP/1.1 200 Bad Request
     *     {
     *          "status": false,
     *          "message": "Something wen't wrong please try again"
     *     }
     */
    public function delete_company_user(Request $request)
    {
        try {
            $userRequest = $request->all();
            $user = $request->user();

            $fields['user_id'] = [
                'required',
                'integer',
                'exists:users,id,deleted_at,NULL'
            ];

            $error = Validator::make($request->all(), $fields, [
                'user_id.required' => trans('label.user_id_required_error_msg'),
                'user_id.exists' => trans('label.user_id_exists_error_msg'),
                'user_id.integer' => trans('label.user_id_integer_error_msg'),
            ]);
            DB::beginTransaction();
            $validationResponse = $this->check_validation($fields, $error, 'Update Company');
            if (!$validationResponse->getData()->status) {
                return $validationResponse;
            }

            if ($user->user_role == array_flip(Role::ROLES)['Company Admin']) {
                if (isset($user->company) && !empty($user->company)) {
                    $userDetails = User::findOrFail($userRequest['user_id']);
                    if ($user->company->id == $userDetails->companyUser->company_id && $userDetails->user_role != array_flip(Role::ROLES)['Company Admin']) {
                        $userDetails->companyUser()->delete();
                        $userDetails->delete();
                        DB::commit();
                        return response()->json(['status' => true, 'message' => trans('label.staff_user_deleted_success_msg')], $this->successStatus);
                    } else {
                        DB::rollback();
                        Auditable::log_audit_data('CompanyUserApiController@delete_company_user Cannot delete company user', null, config('settings.log_type')[1], $userRequest);
                        return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
                    }
                } else {
                    DB::rollback();
                    Auditable::log_audit_data('CompanyUserApiController@delete_company_user Company not found', null, config('settings.log_type')[1], $userRequest);
                    return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
                }
            } else {
                DB::rollback();
                Auditable::log_audit_data('CompanyUserApiController@delete_company_user staff can try to check api', null, config('settings.log_type')[1], $userRequest);
                return response()->json(['status' => false, 'message' => trans('label.invalid_login_credential_error_msg')], $this->successStatus);
            }
        } catch (Exception $ex) {
            DB::rollback();
            Auditable::log_audit_data('CompanyUserApiController@delete_company_user Exception', null, config('settings.log_type')[0], $ex->getMessage());
            return response()->json(['status' => false, 'message' => trans('label.something_went_wrong_error_msg')], $this->successStatus);
        }
    }
}
