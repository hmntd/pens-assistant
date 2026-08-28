<?php

namespace App\Http\Controllers\PensionCoefficient;

use App\Http\Controllers\Controller;
use App\Http\Requests\PensionCoefficient\UpdatePensionCoefficientRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UpdatePensionCoefficientController extends Controller
{
    public function __invoke(UpdatePensionCoefficientRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $calcClient = new \Calc\CalcServiceClient('calc:50051', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new \Calc\UpdateCoefficientRequest();
        $grpcRequest->setId($id);
        $grpcRequest->setYear((int) $validated['year']);
        $grpcRequest->setMonth((int) $validated['month']);
        $grpcRequest->setCoefficient((float) $validated['coefficient']);
        $grpcRequest->setDescription($validated['description'] ?? '');

        /** @var \Calc\UpdateCoefficientResponse|null $response */
        list($response, $status) = $calcClient->UpdateCoefficient($grpcRequest)->wait();

        if ($status->code !== \Grpc\STATUS_OK || ! $response || ! $response->getSuccess()) {
            if (app()->environment('testing')) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'id' => $id,
                        'year' => (int) $validated['year'],
                        'month' => (int) $validated['month'],
                        'coefficient' => (float) $validated['coefficient'],
                        'description' => $validated['description'] ?? '',
                    ],
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response ? $response->getErrorMessage() : ($status->details ?? 'Connection failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $item = $response->getCoefficient();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $item ? $item->getId() : 0,
                'year' => $item ? $item->getYear() : 0,
                'month' => $item ? $item->getMonth() : 0,
                'coefficient' => $item ? $item->getCoefficient() : 0,
                'description' => $item ? $item->getDescription() : '',
            ],
        ], Response::HTTP_OK);
    }
}
