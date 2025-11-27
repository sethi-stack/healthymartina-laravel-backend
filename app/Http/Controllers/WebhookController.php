<?php
namespace App\Http\Controllers;
use Carbon\Carbon;

use App\User;
use App\Models\Membresia;
use App\Models\VideoHome;
use App\Models\Coupon;
use Spatie\Newsletter\NewsletterFacade;
use Illuminate\Support\Facades\Hash;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierController;

class WebhookController extends CashierController
{
    /**
     * Handle customer updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerCreated(array $payload)
    {
        if ($payload['data']['object']['id']) {
            $user = new User;
            $check_user = $user->where('email',$payload['data']['object']['email'])->first();
            if ($check_user) {
                $user = $check_user;
            }else{
                $user->email = $payload['data']['object']['email'];
                $names = explode(" ",$payload['data']['object']['name']);
                $user->name = $names[0];
                $user->last_name = @$names[1];
                $user->password = Hash::make($names[0]);
                $user->type = 'mailchimp';
                $user->first_time_login = 1;
                // need to check
                $user->role_id = 1;
            }
            $user->stripe_id = $payload['data']['object']['id'];
            $user->save();
            $user->markEmailAsVerified();
        }
        return $this->successMethod();
        // Handle The Event
    }

    public function handleCustomerSubscriptionCreated(array $payload)
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);

        if ($user) {
            $data = $payload['data']['object'];

            if (! $user->subscriptions->contains('stripe_id', $data['id'])) {
                if (isset($data['trial_end'])) {
                    $trialEndsAt = Carbon::createFromTimestamp($data['trial_end']);
                } else {
                    $trialEndsAt = null;
                }

                $subscription = $user->subscriptions()->create([
                    'name' => $data['metadata']['name'] ?? 'default',
                    'stripe_id' => $data['id'],
                    'stripe_status' => $data['status'],
                    'stripe_plan' =>  $data['plan']['id'] ?? null,
                    'quantity' => $data['quantity'],
                    'trial_ends_at' => $trialEndsAt,
                    'ends_at' => null,
                ]);
                if ($data['plan']['id']) {
                    $plan_id = $data['plan']['id'];
                    $membresia = Membresia::where('product',$plan_id)->first();

                    $user->role_id = $membresia->role->id;
                    $user->markEmailAsVerified();
                    $user->save();
                    $user->updateDefaultPaymentMethod($data['default_payment_method']);
                    $video_tag = VideoHome::all();

                    if ($membresia->role->id == 2) {
                        $tag = $video_tag[0]->individual_tag;
                    }else {
                        $tag = $video_tag[0]->profesional_tag;
                    }
                    NewsletterFacade::subscribeOrUpdate($user->email, [
                        'FNAME' => $user->name,
                        'LNAME' => $user->last_name,
                    ]);
                    NewsletterFacade::addTags([$tag,'FROM_PAYMENT_LINK'], $user->email);
                }
                foreach ($data['items']['data'] as $item) {
                    $subscription->items()->updateOrCreate([
                            'stripe_id' => $item['id'],
                        ], [
                            'stripe_plan' => $item['plan']['id'],
                            'quantity' => $item['quantity'],
                        ]);
                    // $subscription->items()->updateOrCreate([
                    //     'stripe_id' => $item['id'],
                    //     'stripe_plan' => $item['plan']['id'],
                    //     'quantity' => $item['quantity'],
                    // ]);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle coupon updated.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCouponCreated(array $payload)
    {
        if ($payload['data']['object']['id']) {
            $Snippet = Coupon::create([
            'coupon_id' => $payload['data']['object']['id'],
            'coupon_name' => $payload['data']['object']['name'],
            ]);
        }   
    }
}