<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    protected MerchantService $merchantService;

    public function __construct(MerchantService $merchantService)
    {
        $this->merchantService = $merchantService;
    }

    /**
     * Get useful order statistics for the merchant API.
     *
     * @param Request $request [from: string, to: string]
     * @return JsonResponse
     */

     public function orderStats(Request $request): JsonResponse
     {
         $from = $request->input('from');
         $to = $request->input('to');
 
         // Validate the request parameters
         $this->validate($request, [
             'from' => 'required|date',
             'to' => 'required|date|after_or_equal:from',
         ]);
 
         // Format the dates as Carbon instances
         $fromDate = $from ? new \Carbon\Carbon($from) : null;
         $toDate = $to ? new \Carbon\Carbon($to) : null;
 
         // Get order statistics using the MerchantService
         $stats = $this->merchantService->getOrderStatistics($fromDate, $toDate);
 
         return response()->json($stats);
     }

    // public function orderStats(Request $request): JsonResponse
    // {
    //     $from = $request->input('from');
    //     $to = $request->input('to');

    //     // Validate the request parameters
    //     $this->validate($request, [
    //         'from' => 'required|date',
    //         'to' => 'required|date|after_or_equal:from',
    //     ]);

    //     // Format the dates as Carbon instances
    //     $fromDate = $from ? new \Carbon\Carbon($from) : null;
    //     $toDate = $to ? new \Carbon\Carbon($to) : null;


    //     $orderCount = $this->merchantService->getOrderCount($from, $to);
    //     $commissionOwed = $this->merchantService->getCommissionOwed($from, $to);
    //     $revenue = $this->merchantService->getRevenue($from, $to);

    //     $responseData = [
    //         'count' => $orderCount,
    //         'commissions_owed' => $commissionOwed,
    //         'revenue' => $revenue,
    //     ];

    //     return response()->json($responseData, 200);
    // }
}