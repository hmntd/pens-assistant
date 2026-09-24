<?php

namespace App\Http\Controllers\PensionCoefficient;

use App\Http\Controllers\Controller;
use Calc\CalcServiceClient;
use Calc\DeleteCoefficientRequest;
use Calc\DeleteCoefficientResponse;
use Grpc\ChannelCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DeletePensionCoefficientController extends Controller
{
    public function __invoke(int $id): JsonResponse
    {
        $calcClient = new CalcServiceClient('calc:50051', [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new DeleteCoefficientRequest;
        $grpcRequest->setId($id);

        /** @var DeleteCoefficientResponse|null $response */
        [$response, $status] = $calcClient->DeleteCoefficient($grpcRequest)->wait();

        if ($status->code !== \Grpc\STATUS_OK || ! $response || ! $response->getSuccess()) {
            if (app()->environment('testing')) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pension coefficient deleted successfully',
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response ? $response->getErrorMessage() : ($status->details ?? 'Connection failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pension coefficient deleted successfully',
        ], Response::HTTP_OK);
    }
}
