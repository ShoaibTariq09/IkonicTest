<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        $merchant = $this->findMerchantByDomain($data['merchant_domain']);

        if (!$merchant) {
            return; // Or throw an exception based on your application's logic
        }

        $existingOrder = Order::where('external_order_id', $data['order_id'])->first();

        if ($existingOrder) {
            return; // Ignore duplicate order
        }

        $affiliate = $this->findOrCreateAffiliate($merchant, $data['customer_email'], $data['customer_name']);
        $order = new Order();
        $order->merchant_id = $merchant->id;
        $order->affiliate_id = $affiliate->id;
        $order->subtotal = $data['subtotal_price'];
        $order->commission_owed = $data['subtotal_price'] * $affiliate->commission_rate;
        $order->external_order_id = $data['order_id'];
        $order->discount_code = $data['discount_code'];
        $order->save();
    }

    /**
     * Find a merchant by their domain.
     *
     * @param string $domain
     * @return Merchant|null
     */
    protected function findMerchantByDomain(string $domain): ?Merchant
    {
        return Merchant::where('domain', $domain)->first();
    }

    /**
     * Find or create an affiliate for the provided merchant and customer email.
     *
     * @param Merchant $merchant
     * @param string $email
     * @param string $name
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    protected function findOrCreateAffiliate(Merchant $merchant, string $email, string $name): Affiliate
    {
        $existingAffiliate = Affiliate::where('merchant_id', $merchant->id)
            ->whereHas('user', function ($query) use ($email) {
                $query->where('email', $email);
            })
            ->first();

        if ($existingAffiliate) {
            return $existingAffiliate;
        }

        $commissionRate = $merchant->default_commission_rate;

        try {
            $affiliate = $this->affiliateService->register($merchant, $email, $name, $commissionRate);
        } catch (AffiliateCreateException $exception) {
            throw new \RuntimeException('Failed to create affiliate: ' . $exception->getMessage());
        }

        return $affiliate;
    }
}