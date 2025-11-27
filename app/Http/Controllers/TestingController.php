<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Ingrediente;
use App\Models\Instruccion;
use App\Models\Receta;
use App\Notifications\CommentAnsweredNotification;
use App\Notifications\MyResetPassword;
use App\User;
use Backpack\Base\app\Notifications\ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Spatie\Newsletter\NewsletterFacade;
use \Stripe\Stripe;

class TestingController extends Controller
{
    public function optimize()
    {
        Artisan::call('optimize');
        return "Cache limpio";
    }

    public function migrate()
    {
        $exitCode = Artisan::call('migrate', [
            '--force' => true,
        ]);
        return $exitCode;
    }

    public function addUppercase()
    {
        $ingredients = Ingrediente::all();
        foreach ($ingredients as $ingredient) {
            
            $pos = strpos($ingredient->nombre, '.');
            if($pos){
                dump($ingredient->nombre);
            }
            /*if ($pos) {
                dump($ingredient->nombre);
                $pos += 2;
                $firts_character = $pos + 1;
                dump($pos);

                $substring = substr($ingredient->nombre, 0, $pos);
                dump($substring);
                $substring_to_fix = substr($ingredient->nombre, $pos, $firts_character);

                dump($substring_to_fix);

                $substring2 = ucfirst($substring_to_fix);

                dump($substring2);


                $new_string = $substring . $substring2;
                dd($new_string);
                $ingredient->nombre = $new_string;

                dump($ingredient->nombre);

                return 0;
            }*/
        }
    }

    public function removeParenthesis()
    {
        //$ingredients = Ingrediente::all();
        $ingredients = Ingrediente::all();
        foreach ($ingredients as $ingredient) {
            dump($ingredient->nombre);
            $pos = strpos($ingredient->nombre, '(');
            if ($pos) {
                dump('------------');
                $ingredient->nombre = str_replace(' (', '.', $ingredient->nombre);
                $ingredient->nombre = str_replace(')', '', $ingredient->nombre);

                $dot_pos = strpos($ingredient->nombre, '.') + 1;
                $substring = substr($ingredient->nombre, 0, $dot_pos);
                $substring2 = ucfirst(substr($ingredient->nombre, $dot_pos));
                $converted = $substring . ' ' . $substring2;
                $ingredient->nombre = $converted;
                $ingredient->save();
            }
        }
    }

    public function removeParenthesis2()
    {
        //$ingredients = Ingrediente::all();
        $ingredients = Instruccion::all();
        foreach ($ingredients as $ingredient) {
            $pos = strpos($ingredient->nombre, '(');
            if ($pos) {
                $ingredient->nombre = str_replace(' (', '.', $ingredient->nombre);
                $ingredient->nombre = str_replace(')', '', $ingredient->nombre);

                $dot_pos = strpos($ingredient->nombre, '.') + 1;
                $substring = substr($ingredient->nombre, 0, $dot_pos);
                $substring2 = ucfirst(substr($ingredient->nombre, $dot_pos));
                $converted = $substring . ' ' . $substring2;
                $ingredient->nombre = $converted;

                $ingredient->save();
            }
        }

        return "Done";
    }

    public function testMailchimp()
    {
        NewsletterFacade::subscribeOrUpdate('alatorre@braigo.mx', [
            'FNAME' => 'Chris',
            'LNAME' => 'Alatorre',
        ]);
        NewsletterFacade::addTags(['weekly_reminders'], 'alatorre@braigo.mx');

        NewsletterFacade::removeTags(['new_updates'], 'alatorre@braigo.mx');

        return 1;
    }

    public function notifyComment()
    {
        $receta = Receta::first();
        $comment = Comment::latest()->first();
        $comment->notify(new CommentAnsweredNotification($receta)); // To send
        return (new CommentAnsweredNotification($receta))->toMail($receta); // To preview
    }

    public function notifyResetPasswowrd()
    {
        $user = User::first();
        $user->notify(new MyResetPassword('token', User::firt()));
        return 0;
    }

    public function dd()
    {
        $receta = Receta::first();
        $response = \file_get_contents('healthymartinaweb.test/create-entries/' . 'wfwdkjf');
        dd($response);
    }

       public function testStripe(Request $rquest)
    {
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);
        $checkout_session = $stripe->checkout->sessions->create([
        'line_items' => [[
            # Provide the exact Price ID (e.g. pr_1234) of the product you want to sell
            'price' => 'price_1K5xwHDdKJhAlBDMGadXJVAH',
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'success_url' => 'http://127.0.0.1:8000/success_stripe',
        'cancel_url' => 'http://127.0.0.1:8000/cancel_stripe',
        'automatic_tax' => [
            'enabled' => true,
        ],
        'allow_promotion_codes'=>true,
        ]);
        dd($checkout_session);
        return redirect()->away($checkout_session->url);

    }

    public function stripeSuccess(Request $rquest)
    {
        $key = \config('services.stripe.secret');
        $stripe = new \Stripe\StripeClient($key);
        $checkout_session = $stripe->checkout->sessions->retrieve(
        'cs_test_b1BWSBwIRLf12f4WnmG8yDKfgZrbOoh7CRxm7wzSRusshrsDtgRzSCfPdS',
        []
        );
        
    }
    public function stripeCancel(Request $rquest)
    {
        dd($request->all());
    }
}
