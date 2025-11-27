<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Ingrediente;
use App\Models\NotificationPreference;
use App\Models\Receta;
use App\Models\WizardProgress;
use App\Models\YoutubeChannel;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
     */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
        $user = '';
        $this->middleware(function ($request, $next) {
            $this->user = Auth::user();
            $preferences = NotificationPreference::whereUserId($this->user->id)->first();
            if (!$preferences) {
                $preferences = NotificationPreference::create(['user_id' => $this->user->id]);
            }
            $wizard_progress = WizardProgress::whereUserId($this->user->id)->first();
            if (!$wizard_progress) {
                $wizard_progress = WizardProgress::create(['user_id' => $this->user->id]);
            }
            $recipes_list = Receta::all();
            $ingredients_list = Ingrediente::all();
            $calendars_lists = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
            $yt_channel = YoutubeChannel::first();
            if (session('bookmark') && $request->request->get('num_tiempo')) {
                $reload = true;
            } else {
                $reload = false;
            }
            View::share(['calendars_lists' => $calendars_lists, 'reload' => $reload, 'yt_channel' => $yt_channel, 'recipes_list' => $recipes_list, 'ingredients_list' => $ingredients_list, 'preferences' => $preferences, 'user' => $this->user, 'wizard_progress' => $wizard_progress]);

            return $next($request);
        });

    }
}
