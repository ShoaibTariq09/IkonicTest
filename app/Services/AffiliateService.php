<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     * @throws AffiliateCreateException
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        if ($this->emailExistsAsMerchant($email)) {
            throw new AffiliateCreateException('The provided email is already registered as a merchant.');
        }

        if ($this->emailExistsAsAffiliate($email)) {
            throw new AffiliateCreateException('The provided email is already registered as an affiliate.');
        }

        $discountCode = $this->apiService->createDiscountCode($merchant)['code'];
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt('password'), // You may update this as per your requirements
                'type' => User::TYPE_AFFILIATE,
            ]);

        $affiliate = Affiliate::create([
            'user_id' =>  $user->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode,
        ]);

        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }

    /**
     * Check if the provided email already exists as a merchant.
     *
     * @param string $email
     * @return bool
     */
    protected function emailExistsAsMerchant(string $email): bool
    {
        return User::where('email', $email)->where('type', User::TYPE_MERCHANT)->exists();
    }

    /**
     * Check if the provided email already exists as an affiliate.
     *
     * @param string $email
     * @return bool
     */
    protected function emailExistsAsAffiliate(string $email): bool
    {
        return User::where('email', $email)->where('type', User::TYPE_AFFILIATE)->exists();
    }

}