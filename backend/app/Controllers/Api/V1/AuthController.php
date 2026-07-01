<?php

namespace App\Controllers\Api\V1;

use App\Services\AuthService;

class AuthController extends BaseApiController
{
    /**
     * POST /api/v1/auth/login
     * Body: { "email": "...", "password": "..." }
     */
    public function login()
    {
        $input = $this->jsonInput();

        $rules = [
            'email'    => 'required|valid_email|max_length[191]',
            'password' => 'required|string',
        ];

        if (! $this->validateData($input, $rules)) {
            return $this->respondError(
                422,
                'validation_error',
                'The submitted data is invalid.',
                $this->validator->getErrors()
            );
        }

        $result = (new AuthService())->attemptLogin(
            (string) $input['email'],
            (string) $input['password']
        );

        if ($result === null) {
            // Same response for unknown email and wrong password: do not reveal
            // which accounts exist.
            return $this->respondError(401, 'invalid_credentials', 'Invalid email or password.');
        }

        return $this->respondSuccess($result);
    }
}
