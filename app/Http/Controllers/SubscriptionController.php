<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

Use App\User;
use App\Models\Membresia;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;
use Stripe;

use Session;

use Exception;


class SubscriptionController extends Controller

{

    public function retrievePlans() {
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);
        
        $plansraw = $stripe->plans->all();
        $plans = $plansraw->data;
        
        foreach($plans as $plan) {
            $prod = $stripe->products->retrieve(
                $plan->product,[]
            );
            $plan->product = $prod;
        }
        return $plans;
    }
    public function index()

    {
        $plans = $this->retrievePlans();
        
        $stripe = new \Stripe\StripeClient(
            'stripe_secret_key'
          );
          $intent = $stripe->setupIntents->create([
            'payment_method_types' => ['card'],
          ]);
        
        $user = Auth::user();
        
        return view('subscription.create', [
            'user'=>$user,
            'intent' => $intent,
            'plans' => $plans
        ]);

    }

    public function membresia()
    {
        $plans = $this->retrievePlans();
        $user = Auth::user();
        $stripe = new \Stripe\StripeClient(
            'stripe_secret_key'
          );
          $intent = $user->createSetupIntent();
      //  $membresia = Subscription::where('role_id', $user->role->id)->get();
        $membresias = Membresia::where('activa', 1)->orderBy('orden', 'desc')->get();
        return view('precios', compact('user','membresias','intent','plans'));
       // return view('membresia', compact('user', 'membresias'));
    }

    public function orderPost(Request $request)
    {
       $user = Auth::user();
       $paymentMethod = $request->input('payment_method');
                   
       $user->createOrGetStripeCustomer();
       $user->addPaymentMethod($paymentMethod);
       $plan = $request->input('plan'); 
        $membresia = Membresia::where('product',$plan)->first();
        $user->role_id = $membresia->role->id;
        $user->save();     
        try {
          $newSubscription =  $user->newSubscription('default', $plan)->create($paymentMethod);
       } catch (\Exception $e) {
          return response()->json(['success' => false,'info' => 'Error creating subscription. ' . $e->getMessage()]);
       }
       return response()->json(['success' => true,'info' => 'Membresía exitosa']);
      }

}
