<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Ingrediente;
use App\Models\NotificationPreference;
use App\Models\Receta;
use App\Models\Snippet;
use App\Models\WizardProgress;
use App\Models\YoutubeChannel;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use DB;

class BaseController extends Controller
{
    public function __construct()
    {
        $user = '';
        $this->middleware('auth');
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
            $recipes_list = Receta::all()->sortBy("free");
            $ingredients_list = Ingrediente::all();
            $calendars_lists = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
            $yt_channel = YoutubeChannel::first();

            $templates = Template::all()->sortBy("id");

            if (session('bookmark') && $request->request->get('num_tiempo')) {
                $reload = true;
            } else {
                $reload = false;
            }
             $nutritionals = DB::table('nutritional_preferences')->where('user_id',Auth::user()->id)->first();
                if ($nutritionals) {
                    $nutritionals_info = json_decode($nutritionals->nutritional_info);
                }else{
                    $nutritionals_info = config()->get('constants.nutritients');
                }
                $nutritionals_info = json_decode(json_encode($nutritionals_info), FALSE);
                $snippets = Snippet::where('user_id',Auth::user()->id)->orderBy('id', 'DESC')->get();
                foreach ($nutritionals_info as $key => $value) {
                    if ($value->mostrar == 1) {
                        $filter_info[] = $value->id;
                    }
                }
            View::share(['templates' => $templates, 'filter_info'=> $filter_info, 'nutritionals_info'=> $nutritionals_info, 'snippets'=> $snippets, 'calendars_lists' => $calendars_lists, 'reload' => $reload, 'yt_channel' => $yt_channel, 'recipes_list' => $recipes_list, 'ingredients_list' => $ingredients_list, 'preferences' => $preferences, 'user' => $this->user, 'wizard_progress' => $wizard_progress]);

            return $next($request);
        });
    }

    public function logout()
    {
        //dd('ccxx');
        Auth::logout();
        session()->forget('lastActivityTime');
        session()->forget('calendario_id');
        session()->flash('logout', 'Has cerrado sesión.');
        return redirect('login');
    }
}
