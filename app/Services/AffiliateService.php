<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
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
     */


     public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // Check if the email is already used by a merchant
        $merchantWithEmail = Merchant::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists();

        // Check if the email is already used by a affiliate
        $affiliateWithEmail = Affiliate::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists();

        if ($merchantWithEmail || $affiliateWithEmail ) {
            throw new AffiliateCreateException("Email is already in use");
        }

        // Continue creating the affiliate if the email is not associated with a merchant
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => '123456789',
            'type' => User::TYPE_AFFILIATE,
        ]);

        // Create a discount code using the ApiService
        $discountCodeResponse = $this->apiService->createDiscountCode();
        $discountCode = $discountCodeResponse['code'];

        // Create the affiliate
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode,
        ]);

        // Send affiliate creation email
        Mail::to($user->email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
