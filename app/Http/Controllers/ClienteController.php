<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteRequest as StoreRequest;
use App\User;
use App\Models\Membresia;
use App\Models\VideoHome;
use Spatie\Newsletter\NewsletterFacade;
use Laravel\Cashier\Subscription;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Hash;

class ClienteController extends Controller
{

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function getRegistro()
    {
        return view('registro');
    }

    public function getLogin()
    {
        return view('/login');
    }

    public function postRegistro(StoreRequest $request)
    {
        if ($request->honeypot) {
            return redirect()->back()
                ->withSuccess('hp-bot'); 
        }
        $rules = [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'nombre' => 'required',
            'apellidos' => 'required',
        ];

        $customMessages = [
            'email.required' => 'El correo es requerido',
            'email.email' => 'El formato del correo no es correcto',
            'email.unique' => 'El correo ya ha sido registrado',
            'password.required' => 'La contraseña es requerida',
            'password.confirmed' => 'La confirmacion de contraseña no coincide',
            'password.min' => 'Proporcione una contraseña de al menos 6 caracteres.',
            'nombre.required' => 'El nombre es requerido',
            'apellidos.required' => 'El apellido es requerido',
        ];

        $this->validate($request, $rules, $customMessages);

        // $cliente = new Cliente;
        // $cliente->nombre = $request->request->get("nombre");
        // $cliente->apellidos = $request->request->get("apellidos");
        // $cliente->contrasena = $request->request->get("password");
        // $cliente->email = $request->request->get("email");
        // $cliente->membresia_id = '3';
        // $cliente->save();

        $user = new User;
        $user->name = $request->request->get("nombre");
        $user->last_name = $request->request->get("apellidos");
        $user->password = Hash::make($request->request->get("password"));
        $user->email = $request->request->get("email");
        $user->role_id = 1;
        $user->save();

        FacadesAuth::login($user);
        // $user->sendEmailVerificationNotification();
        //dd(Auth::check(), Auth::user());

        // $cliente->notify(new UserRegistered($request->request->get("email"), $request->request->get("password")));

        return redirect()->intended('/membresia');
    }

    public function postLogin(Request $request)
    {
        // dd($request->input('recordar-cuenta'));
        $remember = $request->input('recordar-cuenta') ? true : false;
        if (FacadesAuth::attempt($request->input('login'), $remember)) {
            // FacadesAuthentication passed...
            $cliente = FacadesAuth::user();
            // $totalProductsWishlist = $user->wishlist()->count();
            // session(compact('totalProductsWishlist'));
            $user = auth()->user();
            if ($user->type=='mailchimp') {
                if ($user->first_time_login) {
                        $customer_details = $user->asStripeCustomer();
                        $subscribe = $customer_details->subscriptions->data[0];  // Assuming all prevoius subscription are already cancel get lastest one.

                    if ($subscribe){
                        if (!$user->subscriptions->contains('stripe_id', $subscribe['id'])) {
                            $subscription = $user->subscriptions()->create([
                                'name' => $subscribe['metadata']['name'] ?? 'default',
                                'stripe_id' => $subscribe['id'],
                                'stripe_status' => $subscribe['status'],
                                'stripe_plan' =>  $subscribe['plan']['id'] ?? null,
                                'quantity' => $subscribe['quantity'],
                                'trial_ends_at' => null,
                                'ends_at' => null,
                            ]);
                            if ($subscribe['plan']['id']) {
                                $plan_id = $subscribe['plan']['id'];
                                $membresia = Membresia::where('product',$plan_id)->first();

                                $user->role_id = $membresia->role->id;
                                $user->markEmailAsVerified();
                                $user->first_time_login = 0;
                                $user->save();
                                $user->updateDefaultPaymentMethod($subscribe['default_payment_method']);
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
                            foreach ($subscribe['items']['data'] as $item) {
                            $subscription->items()->create([
                                'stripe_id' => $item['id'],
                                'stripe_plan' => $item['plan']['id'],
                                'quantity' => $item['quantity'],
                            ]);
                            }
                        }else {
                            $subscription = $user->subscription();
                            if ($subscription->stripe_id == $subscribe['id']) {  
                                if (isset($subscribe['status'])) {
                                        $subscription->stripe_status = $subscribe['status'];
                                }

                                $subscription->save();
                                $user->first_time_login = 0;
                                $user->save();
                            }
                        }
                    }
                }

            }

            session(['calendario_id' => auth()->user()->calendario_id]);
            return redirect('/recetario');
        } else {
            return redirect()->back()->withErrors('Email o Password Incorrecto');
        }
    }

    public function logout(Request $request)
    {
        FacadesAuth::logout();
        return redirect()->route('login');
    }
}
