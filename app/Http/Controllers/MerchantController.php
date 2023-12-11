<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {

        ## Incoming dates
        $from   = Carbon::parse($request->from);
        $to     = Carbon::parse($request->to);

        $orders = Merchant::find(auth()->user()->id)->orders()->whereBetween('created_at', [$from, $to])->get();

        $count           = $orders->count();
        $commissionsOwed = $orders->where('affiliate_id', '!=', null)->sum('commission_owed');
        $revenue         = $orders->sum('subtotal');

        return response()->json([
            'count' => $count,
            'commissions_owed' => $commissionsOwed,
            'revenue' => $revenue,
        ]);
    }
}
