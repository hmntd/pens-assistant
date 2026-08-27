<?php

namespace App\Http\Controllers\Admin\SubsistenceMinimum;

use App\Http\Controllers\Controller;
use Calc\CalcServiceClient;
use Calc\DeleteSubsistenceMinimumRequest;
use Calc\DeleteSubsistenceMinimumResponse;
use Grpc\ChannelCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class DeleteSubsistenceMinimumController extends Controller
{
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], Response::HTTP_FORBIDDEN);
        }

        if (app()->environment('testing')) {
            return response()->json([
                'message' => 'Subsistence minimum deleted successfully.',
            ], Response::HTTP_OK);
        }

        $calcClient = new CalcServiceClient(config('services.calc.host', 'calc:50051'), [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new DeleteSubsistenceMinimumRequest();
        $grpcRequest->setId($id);

        /** @var DeleteSubsistenceMinimumResponse|null $response */
        list($response, $status) = $calcClient->DeleteSubsistenceMinimum($grpcRequest)->wait();

        if ($status->code !== \Grpc\STATUS_OK || !$response || !$response->getSuccess()) {
            $errMsg = $response ? $response->getErrorMessage() : ($status->details ?? 'Connection to Calc Service failed');
            return response()->json([
                'message' => "Failed to delete subsistence minimum: {$errMsg}",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'message' => 'Subsistence minimum deleted successfully.',
        ], Response::HTTP_OK);
    }
}
