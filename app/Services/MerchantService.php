<?php

namespace App\Services;

use App\Jobs\PayoutOrderJob;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class MerchantService
{
    /**
     * Register a new user and associated merchant.
     *
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return Merchant
     */
    public function register(array $data): Merchant
    {
        // $userExist = User::where('name',$data['name'])->where('email',$data['email'])->where('type',User::TYPE_MERCHANT)->where('password',$data['api_key'])->first();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['api_key'],
            'type' => User::TYPE_MERCHANT,
        ]);

        $merchant = Merchant::create([
            'user_id' => $user->id,
            'domain' => $data['domain'],
            'display_name' => $data['name'],
        ]);

        return $merchant;
    }

    /**
     * Update the merchant's user.
     *
     * @param User $user
     * @param array{domain: string, name: string, email: string, api_key: string} $data
     * @return void
     */
    public function updateMerchant(User $user, array $data)
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['api_key']),
        ]);

        $merchant = $user->merchant;
        if ($merchant) {
            $merchant->update([
                'domain' => $data['domain'],
                'display_name' => $data['name'],
            ]);
        }
    }

    /**
     * Find a merchant by their email.
     *
     * @param string $email
     * @return Merchant|null
     */
    public function findMerchantByEmail(string $email): ?Merchant
    {
        $user = User::where('email', $email)->first();

        if ($user) {
            return $user->merchant;
        }

        return null;
    }

    /**
     * Pay out all of an affiliate's unpaid orders.
     *
     * @param Affiliate $affiliate
     * @return void
     */
    public function payout(Affiliate $affiliate)
    {
        $unpaidOrders = Order::where('affiliate_id', $affiliate->id)
            ->where('payout_status', Order::STATUS_UNPAID)
            ->get();

        foreach ($unpaidOrders as $order) {
            dispatch(new PayoutOrderJob($order));
        }
    }

    /**
     * Get order statistics for the specified date range.
     *
     * @param Carbon|null $fromDate
     * @param Carbon|null $toDate
     * @return array ['count' => int, 'revenue' => float, 'commissions_owed' => float]
     */
    public function getOrderStatistics(?Carbon $fromDate, ?Carbon $toDate): array
    {
        $query = Order::query()->where('merchant_id', auth()->user()->merchant->id);

        if ($fromDate && $toDate) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        } elseif ($fromDate) {
            $query->where('created_at', '>=', $fromDate);
        } elseif ($toDate) {
            $query->where('created_at', '<=', $toDate);
        }

        $count = $query->count();
        $revenue = $query->sum('subtotal');
        $commissions_owed = $query->where('affiliate_id', '<>', null)->sum('commission_owed');

        return compact('count', 'revenue', 'commissions_owed');
    }

            /**
     * Get the total number of orders within a given date range.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return int
     */
    public function getOrderCount($from, $to): int
    {
        return Order::whereBetween('created_at', [$from, $to])->count();
    }

    /**
     * Get the total amount of unpaid commissions for orders with an affiliate within a given date range.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    public function getCommissionOwed( $from, $to): float
    {
        return Order::whereBetween('created_at', [$from, $to])
            ->whereNotNull('affiliate_id')
            ->where('payout_status', Order::STATUS_UNPAID)
            ->sum('commission_owed');
    }

    /**
     * Get the total revenue from orders within a given date range.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    public function getRevenue( $from,  $to): float
    {
        return Order::whereBetween('created_at', [$from, $to])->sum('subtotal');
    }
}