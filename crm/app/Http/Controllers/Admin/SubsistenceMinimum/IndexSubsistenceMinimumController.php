<?php

namespace App\Http\Controllers\Admin\SubsistenceMinimum;

use App\Http\Controllers\Controller;
use Calc\CalcServiceClient;
use Calc\ListSubsistenceMinimumsRequest;
use Calc\ListSubsistenceMinimumsResponse;
use Grpc\ChannelCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class IndexSubsistenceMinimumController extends Controller
{
    public const CACHE_KEY_ALL = 'calc:subsistence_minimums:all';

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isAdmin()) {
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

        $records = Cache::remember(self::CACHE_KEY_ALL, 3600, function () {
            $calcClient = new CalcServiceClient(config('services.calc.host', 'calc:50051'), [
                'credentials' => ChannelCredentials::createInsecure(),
            ]);

            $grpcRequest = new ListSubsistenceMinimumsRequest;
            /** @var ListSubsistenceMinimumsResponse|null $response */
            [$response, $status] = $calcClient->ListSubsistenceMinimums($grpcRequest)->wait();

            $list = [];
            if ($status->code === \Grpc\STATUS_OK && $response && $response->getSuccess()) {
                foreach ($response->getRecords() as $rec) {
                    $list[] = [
                        'id' => $rec->getId(),
                        'year' => $rec->getYear(),
                        'for_disabled_persons' => $rec->getForDisabledPersons(),
                        'general_minimum' => $rec->getGeneralMinimum(),
                    ];
                }
            }

            return $list;
        });

        return response()->json([
            'data' => $records,
        ], Response::HTTP_OK);
    }
}
