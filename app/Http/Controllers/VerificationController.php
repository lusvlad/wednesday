<?php

namespace App\Http\Controllers;

use App\Exceptions\AppException;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerificationController extends Controller
{
    private VerificationService $verificationService;

    /**
     * @param VerificationService $verificationService
     */
    public function __construct(VerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ValidationException
     * @throws AppException
     */
    public function sendCode(Request $request): JsonResponse
    {
        // If email is invalid throttle will block route anyway.
        // In real app there be validated user
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $this->verificationService->create($request->post('email'));

        return new JsonResponse('ok');
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws ValidationException
     * @throws AppException
     */
    public function checkCode(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email',
            'code' => 'required',
        ]);

        $this->verificationService->verify(
            $request->post('email'),
            $request->post('code')
        );

        return new JsonResponse('ok');
    }
}
