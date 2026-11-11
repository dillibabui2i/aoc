<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PolicyAcceptRequest;
use App\Http\Requests\PolicyListRequest;
use App\Http\Requests\PolicyStatusRequest;
use App\Http\Resources\Policy\PolicyIndexResource;
use App\Http\Resources\Policy\StatusResource;
use App\Services\Policy\PolicyService;
use Illuminate\Http\JsonResponse;

class PolicyController extends Controller
{
    /**
     * @param PolicyService $policyService
     */
    public function __construct(protected PolicyService $policyService)
    {
    }

    /**
     * @param PolicyListRequest $request
     * @return PolicyIndexResource
     */
   public function index(PolicyListRequest $request): PolicyIndexResource
   {
       $policies = $this->policyService->getLatestPolicies($request->toDTO());

        return new PolicyIndexResource($policies);
   }

    /**
     * @param PolicyAcceptRequest $request
     * @return JsonResponse
     */
   public function accept(PolicyAcceptRequest $request): JsonResponse
   {
       $this->policyService->acceptPolicy($request->toDTO());

       return response()->json(['message' => __('messages.policy_accepted')]);
   }

    /**
     * @param PolicyStatusRequest $request
     * @return StatusResource
     */
   public function status(PolicyStatusRequest $request): StatusResource
   {
       $status = $this->policyService->checkStatus($request->toDTO());

       return new StatusResource($status);
   }
}
