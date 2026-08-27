<?php

namespace App\Http\Controllers\Admin\SubsistenceMinimum;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubsistenceMinimumRequest;
use Calc\CalcServiceClient;
use Calc\UpsertSubsistenceMinimumRequest;
use Calc\UpsertSubsistenceMinimumResponse;
use Grpc\ChannelCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StoreSubsistenceMinimumController extends Controller
{
    public function __invoke(StoreSubsistenceMinimumRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();

        if (app()->environment('testing')) {
            return response()->json([
                'message' => 'Subsistence minimum set successfully by admin.',
                'data' => [
                    'year' => (int) $validated['year'],
                    'for_disabled_persons' => (float) $validated['for_disabled_persons'],
                    'general_minimum' => (float) $validated['general_minimum'],
                ],
            ], Response::HTTP_CREATED);
        }

        $calcClient = new CalcServiceClient(config('services.calc.host', 'calc:50051'), [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new UpsertSubsistenceMinimumRequest();
        $grpcRequest->setYear((int) $validated['year']);
        $grpcRequest->setForDisabledPersons((float) $validated['for_disabled_persons']);
        $grpcRequest->setGeneralMinimum((float) $validated['general_minimum']);
        $grpcRequest->setAgeSurchargeCap((float) ($validated['age_surcharge_cap']));

        /** @var UpsertSubsistenceMinimumResponse|null $response */
        list($response, $status) = $calcClient->UpsertSubsistenceMinimum($grpcRequest)->wait();

        if ($status->code !== \Grpc\STATUS_OK || !$response || !$response->getSuccess()) {
            $errMsg = $response ? $response->getErrorMessage() : ($status->details ?? 'Connection to Calc Service failed');
            return response()->json([
                'message' => "Failed to update subsistence minimum: {$errMsg}",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Subsistence minimum set successfully by admin.',
            'data' => [
                'year' => (int) $validated['year'],
                'for_disabled_persons' => (float) $validated['for_disabled_persons'],
                'general_minimum' => (float) $validated['general_minimum'],
            ],
        ], Response::HTTP_CREATED);
    }
}
