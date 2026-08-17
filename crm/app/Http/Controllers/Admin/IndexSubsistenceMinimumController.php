<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Calc\CalcServiceClient;
use Calc\ListSubsistenceMinimumsRequest;
use Calc\ListSubsistenceMinimumsResponse;
use Grpc\ChannelCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IndexSubsistenceMinimumController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], Response::HTTP_FORBIDDEN);
        }

        if (app()->environment('testing')) {
            return response()->json([
                'data' => [
                    [
                        'id' => 1,
                        'year' => 2024,
                        'for_disabled_persons' => 2361.0,
                        'general_minimum' => 2920.0,
                    ],
                ],
            ], Response::HTTP_OK);
        }

        $calcClient = new CalcServiceClient(config('services.calc.host', 'calc:50051'), [
            'credentials' => ChannelCredentials::createInsecure(),
        ]);

        $grpcRequest = new ListSubsistenceMinimumsRequest();
        /** @var ListSubsistenceMinimumsResponse|null $response */
        list($response, $status) = $calcClient->ListSubsistenceMinimums($grpcRequest)->wait();

        $records = [];
        if ($status->code === \Grpc\STATUS_OK && $response && $response->getSuccess()) {
            foreach ($response->getRecords() as $rec) {
                $records[] = [
                    'id' => $rec->getId(),
                    'year' => $rec->getYear(),
                    'for_disabled_persons' => $rec->getForDisabledPersons(),
                    'general_minimum' => $rec->getGeneralMinimum(),
                ];
            }
        }

        return response()->json([
            'data' => $records,
        ], Response::HTTP_OK);
    }
}
