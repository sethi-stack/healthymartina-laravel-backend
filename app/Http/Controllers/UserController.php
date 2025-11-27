<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Membresia;
use App\Models\NotificationPreference;
use App\Models\Subscription;
use App\Models\WizardProgress;
use App\Models\Snippet;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Newsletter\NewsletterFacade;
use \Stripe\Stripe;
use DB;

class UserController extends BaseController
{
    public function show()
    {
        $user = Auth::user();
        return view('perfil', compact('user'));
    }

    public function update(Request $request)
    {
        
        $user = Auth::user();
        User::find($user->id)->update($request->user);
         if (auth()->user()->hasRole('professional') && isset($request->user['bname'])) {
            $user->bname = $request->user['bname'];
            $user->profession = $request->user['profession'];
            $user->website = $request->user['website'];
            $user->color = $request->user['color'];
            $user->bemail = $request->user['bemail'];
            $user->save();
         }
        if ($request->from_assist) {
            session()->flash('open_wizard_preferences', 1);
        }

        if ($user->completed_profile) {
            WizardProgress::updateOrCreate(['user_id' => $this->user->id], ['user_id' => $this->user->id, 'profile' => 1]);
        }
    
        session()->flash('info', 'Cambios guardados exitosamente');
        if ($request->business_profile) {
            return redirect(route('user.business'));
        }
        return redirect(route('user.show'));
    }


     public function business()
    {
        $user = Auth::user();
        return view('empresa', compact('user'));
    }

    public function updateSetupWizardProgress($type)
    {
        $wizard_progress = WizardProgress::whereUserId(Auth::user()->id)->first();
        $wizard_progress->{$type} = 1;

        if ($wizard_progress->filter && $wizard_progress->bookmark && $wizard_progress->calendar) {
            $wizard_progress->next_steps = 1;
        } else {
            $wizard_progress->next_steps = 0;
        }

        $wizard_progress->save();

        return "Updated progress";
    }

    public function updatePhotobusiness(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $user->bimage = Storage::put('users/profile-pictures', $request->user['bimage']);
        //$user->image = 'users/profile-pictures';
        $user->save();
        session()->flash('info', 'Imagen actualizada');
        return redirect(route('user.business'));
    }

    public function deletePhotobusiness()
    {
        $user = User::find(Auth::user()->id);
        $user->bimage = 'profile/placeholder.png';
        $user->save();
        session()->flash('info', 'Imagen eliminada');
        return redirect(route('user.business'));
    }

    public function updatePhoto(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $user->image = Storage::put('users/profile-pictures', $request->user['image']);
        //$user->image = 'users/profile-pictures';
        $user->save();
        session()->flash('info', 'Imagen actualizada');
        return redirect(route('user.show'));
    }

    public function deletePhoto()
    {
        $user = User::find(Auth::user()->id);
        $user->image = 'profile/placeholder.png';
        $user->save();
        session()->flash('info', 'Imagen eliminada');
        return redirect(route('user.show'));
    }

    public function createSnippet(Request $request)
    {
            $this->validate($request, [
                'title' => 'required|max:255',
                //'content' => 'required',
            ]);
            if(empty($request->content))
            {
                $input_content ="";
            }
            else{
                $input_content = $request->content;
            }
            $Snippet = Snippet::create([
            'user_id' => Auth::user()->id,
            'title' => $request->title,
            'content' => $input_content,
            ]);
            if ($request->ajax()) {
                $json_data['message'] = "Preferencias actualizadas";
                $json_data['status'] = "success";
                $json_data['snippet_id'] = $Snippet->id;
                echo json_encode($json_data);
                die();
            }else{
                session()->flash('info', 'Preferencias actualizadas');
            }
            return redirect(route('user.preferences'));
    }
    public function updateSnippet(Request $request,$id)
    {
        $this->validate($request, [
                'title' => 'required|max:255',
                'content' => 'required',
            ]);
         $Snippet = Snippet::where('id', $id)->update([
            'user_id' => Auth::user()->id,
            'title' => $request->title,
            'content' => $request->content,
            ]);
            session()->flash('info', 'Preferencias actualizadas');

            return redirect(route('user.preferences'));
    }
    public function deleteSnippet(Request $request, $id)
    {
        Snippet::where('id', $id)->where('user_id', Auth::user()->id)->delete();

        session()->flash('info', 'Plantilla eliminado');

        return redirect(route('user.preferences'));
    }
    public function preferences()
    {
        $user = Auth::user();
        $preferences = NotificationPreference::whereUserId($user->id)->first();
        $nutritionals = DB::table('nutritional_preferences')->where('user_id',$user->id)->first();
        if ($nutritionals) {
            $nutritionals_info = json_decode($nutritionals->nutritional_info);
        }else{
            $nutritionals_info = config()->get('constants.nutritients');
        }
        
        $nutritionals_info = json_decode(json_encode($nutritionals_info), FALSE);
        return view('preferencias', compact('user', 'preferences','nutritionals_info'));
    }

    public function updatePreferences(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $user->unit_measure = $request->unit_measure;
        if (auth()->user()->hasRole('professional')) {
        $user->theme = $request->theme;
        }
        $user->save();

        $preferences = NotificationPreference::whereUserId(Auth::user()->id)->first();
        if ($preferences) {
            //return "Updated";
            $preferences->weekly_reminders = isset($request->preferences['weekly_reminders']) ? 1 : 0;
            $preferences->new_updates = isset($request->preferences['new_updates']) ? 1 : 0;
            $preferences->mentions = isset($request->preferences['mentions']) ? 1 : 0;
            $preferences->save();
        } else {
            //return "New";

            $preferences = new NotificationPreference();
            $preferences->weekly_reminders = isset($request->preferences['weekly_reminders']) ? 1 : 0;
            $preferences->new_updates = isset($request->preferences['new_updates']) ? 1 : 0;
            $preferences->mentions = isset($request->preferences['mentions']) ? 1 : 0;
            $preferences->user_id = isset($request->preferences['user_id']) ? 1 : 0;
            $preferences->save();
        }

        if ($preferences->weekly_reminders) {
            NewsletterFacade::subscribeOrUpdate($user->email, [
                'FNAME' => $user->name,
                'LNAME' => $user->last_name,
            ]);
            NewsletterFacade::addTags(['weekly_reminders'], $user->email);
        } else {
            NewsletterFacade::removeTags(['weekly_reminders'], $user->email);
        }

        if ($preferences->new_updates) {
            NewsletterFacade::subscribeOrUpdate($user->email, [
                'FNAME' => $user->name,
                'LNAME' => $user->last_name,
            ]);
            NewsletterFacade::addTags(['new_updates'], $user->email);
        } else {
            NewsletterFacade::removeTags(['new_updates'], $user->email);

        }

        if ($request->from_assist) {
            session()->flash('open_wizard_video', 1);
        }

        WizardProgress::updateOrCreate(['user_id' => $this->user->id], ['user_id' => $this->user->id, 'preferences' => 1]);

        $nutritional_info = config()->get('constants.nutritients');
        $nutritional = $request->nutritions;
        if ($nutritional) {
            foreach ($nutritional_info as $key => $value) {
                if (in_array($value['id'],$nutritional)) {
                    $nutritional_info[$key]['mostrar'] = 1;
                }else {
                    $nutritional_info[$key]['mostrar'] = 0;
                }
            }
        } 
        $nutritional_info = json_encode($nutritional_info);
        $nutritional_preferences = DB::table('nutritional_preferences')->where('user_id',$user->id)->first();
        if ($nutritional_preferences) {
             DB::table('nutritional_preferences')->where('user_id',$user->id)->update(
                    ['nutritional_info' => $nutritional_info]
                );
        } else {
            DB::table('nutritional_preferences')->insert(
                    ['nutritional_info' => $nutritional_info, 'user_id' => $user->id]
                );
        }
        
        
        if ($request->ajax()) {
            return "Preferencias actualizadas";
        } else {
            session()->flash('info', 'Preferencias actualizadas');

            return redirect(route('user.preferences'));
        }
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'new_password' => ['required'],
            'new_confirm_password' => ['same:new_password'],
        ]);

        User::find(Auth::user()->id)->update(['password' => Hash::make($request->new_password)]);
        session()->flash('info', 'Contraseña actualizada');

        return redirect(route('user.show'));
    }

    //membresia
    public function retrievePlans()
    {
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);

        //$coupons = $stripe->coupons->all();
        //dd($coupons);
        $plansraw = $stripe->plans->all();
        $plans = $plansraw->data;

        foreach ($plans as $plan) {
            $prod = $stripe->products->retrieve(
                $plan->product, []
            );
            $plan->product = $prod;
        }
        return $plans;
    }
    public function membresia($id = '')
    {
        $noww = Carbon::now();
        $end_period = Carbon::parse('2022-01-01');
        $trial_day = 0; // $end_period->diffInDays($noww);
        $plans = $this->retrievePlans();
        //dd($plans);
        $user = Auth::user();
        $membresias = Membresia::where('activa', 1)->orderBy('orden', 'desc')->get();
        if ($user->subscribed()) {
            $subscription = $user->subscription();
            $customer_details = $user->asStripeCustomer();
            //  dd($customer_details);
            if ($customer_details->subscriptions->data[0]->plan->interval == 'month') {
                if ($customer_details->subscriptions->data[0]->plan->interval_count == 6) {
                    $currentPlan_type = 'semestral-type';
                } else {
                    $currentPlan_type = 'month-type';
                }
            } else {
                $currentPlan_type = 'anual-type';
            }
            $currentPlan = Membresia::where('product', $customer_details->subscriptions->data[0]->plan->id)->first();
            $currentPlanOrder = 99;
            if($currentPlan){
                $currentPlanOrder = $currentPlan->orden;
            }
            if (!empty($id)) {

                $upgrade_member = Membresia::where('id', $id)->where('activa', 1)->orderBy('orden', 'desc')->first();
                // See what the next invoice would look like with a price switch
                // and proration set:
                $items = [
                    [
                        'id' => $subscription->items[0]->stripe_id,
                        'price' => $upgrade_member->product, # Switch to new price
                    ],
                ];
                // Set proration date to this moment:
                $proration_date = time();
                $key = \config('services.stripe.secret');
                $stripe = \Stripe\Stripe::setApiKey($key);
                $invoice = \Stripe\Invoice::upcoming([
                    'customer' => $user->stripe_id,
                    'subscription' => $subscription->stripe_id,
                    'subscription_items' => $items,
                    'subscription_proration_date' => $proration_date,
                ]);
                $auth_intent = auth()->user()->createSetupIntent();
                //dd($invoice);
                return view('precios', compact('invoice', 'user', 'membresias', 'auth_intent', 'upgrade_member', 'plans', 'currentPlan_type'));
            }

            $now = Carbon::now();
            $current_period_end = Carbon::parse($customer_details->subscriptions->data[0]->current_period_end);
            $current_period_start = Carbon::parse($customer_details->subscriptions->data[0]->current_period_start);
            $remaining_day = $current_period_end->diffInDays($now);
            $total_day = $current_period_end->diffInDays($current_period_start);
            return view('membresia', compact('user', 'membresias', 'subscription', 'customer_details', 'remaining_day', 'total_day', 'currentPlan_type','currentPlanOrder'));
        }
        $auth_intent = auth()->user()->createSetupIntent();

        return view('precios', compact('user', 'membresias', 'auth_intent', 'plans', 'trial_day'));
        //
    }
    public function orderPost(Request $request)
    {
        $user = Auth::user();
        $paymentMethod = $request->input('payment_method');

        $user->createOrGetStripeCustomer();
        $user->addPaymentMethod($paymentMethod);
        $plan = $request->input('plan');
        try {
            $now = Carbon::now();
            $end_period = Carbon::parse('2022-01-01');
            $remaining_day = 0; //$end_period->diffInDays($now);
            if ($coupon = $request->coupon_code) {

                $user->newSubscription('default', $plan)
                    ->withCoupon($coupon)
                    ->create($paymentMethod, [
                        'email' => $user->email,
                    ]);

            } else {
                $user->newSubscription('default', $plan)->create($paymentMethod, [
                    'email' => $user->email,
                ]);
            }
            $plan = $request->input('plan');
            $membresia = Membresia::where('product',$plan)->first();
            $user->role_id = $membresia->role->id;
            $user->save();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'info' => 'Error creating subscription. ' . $e->getMessage()]);
        }
        session()->flash('info', 'La suscripción de tu membresía se ha realizado exitosamente');

        return response()->json(['success' => true, 'info' => 'La suscripción de tu membresía se ha realizado exitosamente']);
    }

    public function updateCard(Request $request)
    {
        $user = Auth::user();
        $paymentMethod = $request->input('payment_method');

        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);

        $plan = $request->input('plan');
        try {
            if ($request->number == 'XXXX XXXX XXXX ' . $user->defaultPaymentMethod()->card->last4) {
                $stripe->paymentMethods->update(
                    $paymentMethod,
                    ["billing_details" => [
                        "address" => [
                            "city" => $request->city,
                            "country" => $request->country,
                            "line1" => $request->line1,
                            "postal_code" => $request->postal_code,
                            "state" => $request->state,
                        ],
                        "name" => $request->name,
                    ]]
                );
            } else {
                $expiry = explode('/', $request->expiry);
                $newpaymentMethod = $stripe->paymentMethods->create([
                    'type' => 'card',
                    'card' => [
                        'number' => $request->number,
                        'exp_month' => $expiry[0],
                        'exp_year' => $expiry[1],
                        'cvc' => $request->cvc,
                    ],
                    "billing_details" => [
                        "address" => [
                            "city" => $request->city,
                            "country" => $request->country,
                            "line1" => $request->line1,
                            "postal_code" => $request->postal_code,
                            "state" => $request->state,
                        ],
                        "name" => $request->name,
                    ],
                ]);
                $user->updateDefaultPaymentMethod($newpaymentMethod);
            }
            //  $user->newSubscription('default', $plan)->create($paymentMethod, [
            //      'email' => $user->email
            //  ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'info' => 'Error creating subscription. ' . $e->getMessage()]);
        }
        return response()->json(['success' => true, 'info' => 'Información actualizar']);

    }
    public function updateSubscription(Request $request)
    {
        /// dd($request->all());
        $user = Auth::user();
        $paymentMethod = $request->input('payment_method');

        $user->createOrGetStripeCustomer();
        $user->updateDefaultPaymentMethod($paymentMethod);
        $plan = $request->input('plan');
        $cancel_subscription = $user->subscription();
        try {
            if ($coupon = $request->coupon_code) {
                $user->subscription('default')->swapAndInvoice($plan, [
                    'coupon' => $coupon,
                ]);
            } else {
                $user->subscription('default')->swapAndInvoice($plan);
            }
            $membresia = Membresia::where('product',$plan)->first();
            $user->role_id = $membresia->role->id;
            $user->save();
            // $user->newSubscription('default', $plan)->create($paymentMethod, [
            //     'email' => $user->email
            // ]);
            // $cancel_subscription->cancelNow();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'info' => 'Error creating subscription. ' . $e->getMessage()]);
        }
        return response()->json(['success' => true, 'info' => 'Su pago se ha realizado exitosamente']);
    }

    public function upgrade()
    {
        $user = Auth::user();
        $membresia = Subscription::where('role_id', $user->role->id)->get();
        return view('membresia', compact('user', 'membresia'));
    }

    public function recibos()
    {
        $user = Auth::user();
        $recibos = $user->invoices();
        $invoiceLinks = [];
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);
        foreach ($recibos as $recibo) {
            $in = $stripe->invoices->retrieve(
                $recibo->id,
                []
            );
            $invoiceLinks[$recibo->id] = $in['invoice_pdf'];
        }
        return view('recibos', compact('user', 'recibos', 'invoiceLinks'));
    }
    public function downloadInvoice(Request $request, $plan, $invoiceId)
    {
        return $request->user()->downloadInvoice($invoiceId, [
            'vendor' => 'Healthy Martina',
            'product' => $plan,
        ]);
    }
    public function updateCoupon(Request $request)
    {
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);
        $subscription = $request->user()->subscription();

        try {
            $stripe->subscriptions->update(
                $subscription->stripe_id,
                ['coupon' => '30OFF']
            );
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
        return redirect()->back()->with(['info' => '¡Felicitaciones! Oferta aplicada con éxito a su membresía']);

    }
    public function cancelSubscription(Request $request)
    {
        try {
            $request->user()->subscription()->cancel();
            NewsletterFacade::subscribeOrUpdate($request->user()->email, ['CANCELREAS' => @$request->cancel_description]);
            $tag = 'cancelled_' . $request->checkbox_cancel;
            NewsletterFacade::addTags([$tag], $request->user()->email);
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => $e->getMessage()]);
        }
        return redirect()->back()->with(['info' => 'Membresía Cancelar']);
    }

    public function setDefaultMembresia(Request $request)
    {
        $user = Auth::user();
        $user->role_id = 1;
        NewsletterFacade::subscribeOrUpdate($request->user()->email, [
            'FNAME' => $user->name,
            'LNAME' => $user->last_name,
        ]);

        if ($user->hasVerifiedEmail()) {
            NewsletterFacade::addTags(['Demo'], $request->user()->email);
        } else {
            NewsletterFacade::addTags(['Demo'], $request->user()->email);
        }
        $user->save();

        return redirect()->route('recetario.show')->with(['info' => 'Membresia actualizada']);

    }

    public function checkCoupon(Request $request)
    {

        $check_coupon = Coupon::where('coupon_name', $request->coupon_name)->get();
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);
        foreach ($check_coupon as $key => $value) {
            if ($value->specific_product == null) {
                $coupon_details = $stripe->coupons->retrieve($value->coupon_id);
                if ($coupon_details) {
                    return response()->json(['success' => true, 'info' => $coupon_details]);
                }
            } elseif ($value->specific_product == $request->plan_id) {
                $coupon_details = $stripe->coupons->retrieve($value->coupon_id);
                if ($coupon_details) {
                    if ($coupon_details) {
                        return response()->json(['success' => true, 'info' => $coupon_details]);
                    }
                }
            }
        }
        return response()->json(['success' => false, 'info' => 'Invalid Coupon']);
    }

    // public function ajaxIntermediateMessage(Request $request)
    // {
    //     return response()->json(['status' => 'success', 'message' => 'DUMM']);

    //     if($request->ajax()){
    //         if(session()->has('export-intermediate-message')):
    //             $message = session()->get('export-intermediate-message');
    //             if(!empty($message)):
    //                 return response()->json(['status' => 'success', 'message' => $message]);
    //             else:
    //                     return response()->json(['status' => 'error']);
    //             endif;
    //         else:
    //             return response()->json(['status' => 'error']);
    //         endif;
    //     }
    //     else{
    //         dd("not allowed");
    //     }
    // }
}
