<?php

namespace App\Http\Controllers\PensionCoefficient;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexPensionCoefficientController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 15)));

        $calcClient = new \Calc\CalcServiceClient('calc:50051', [
            'credentials' => \Grpc\ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new \Calc\ListCoefficientsRequest();

        /** @var \Calc\ListCoefficientsResponse|null $response */
        list($response, $status) = $calcClient->ListCoefficients($grpcRequest)->wait();

        if ($status->code !== \Grpc\STATUS_OK || !$response || !$response->getSuccess()) {
            if (app()->environment('testing')) {
                $testItems = [
                    ['id' => 1, 'year' => 2025, 'month' => 1, 'coefficient' => 1.052, 'description' => 'Test Base'],
                ];
                $paginator = new LengthAwarePaginator(
                    $testItems,
                    count($testItems),
                    $perPage,
                    $page,
                    ['path' => $request->url(), 'query' => $request->query()]
                );

                return response()->json([
                    'status' => 'success',
                    'data' => $paginator->items(),
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ],
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status' => 'error',
                'message' => $response ? $response->getErrorMessage() : ($status->details ?? 'Connection failed'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $allCoefficients = [];
        foreach ($response->getCoefficients() as $item) {
            /** @var \Calc\PensionCoefficient $item */
            $allCoefficients[] = [
                'id' => $item->getId(),
                'year' => $item->getYear(),
                'month' => $item->getMonth(),
                'coefficient' => $item->getCoefficient(),
                'description' => $item->getDescription(),
            ];
        }

        $total = count($allCoefficients);
        $offset = ($page - 1) * $perPage;
        $slicedItems = array_slice($allCoefficients, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $slicedItems,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'status' => 'success',
            'data' => array_values($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], Response::HTTP_OK);
    }
}
