<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\Categoria;
use App\Models\ListaIngredientes;
use App\Models\Receta;
use App\User;
use Barryvdh\DomPDF\Facade as PDF;
use DB;
use iio\libmergepdf\Merger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Storage\StorageClient;

use Mail;

class CalendarController extends BaseController
{
    public function calendarioPlanificador(Request $request)
    {
        if(isset($request->karde))
            $this->compose_file('hmartina.appspot.com',['2-el-fav-test-partial-receta.pdf','2-el-fav-test-partial-receta0.pdf','2-el-fav-test-partial-receta1.pdf','2-el-fav-test-partial-receta2.pdf','2-el-fav-test-partial-receta3.pdf','2-el-fav-test-partial-receta4.pdf'],'2-el-fav-test-merged.pdf');
        if (isset($request->id)) {
            $calendario = Calendar::where('id', $request->id)->first();
            if ($calendario) {
                session(['calendario_id' => $calendario->id]);
            }
        }

        if ($request->session()->has('calendario_id')) {
            $check_calender = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->exists();
            if ($check_calender) {
                $calendar = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendar = Calendar::where('user_id', Auth::user()->id)->first();
                if ($calendar) {
                    session(['calendario_id' => $calendar->id]);
                }
            }
        } else {
            $calendar = Calendar::where('user_id', Auth::user()->id)->first();
        }
        $calendarios = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();

        // $path = public_path('uploads_json/'.$calendar->id);
        // if (! File::exists($path)) {
        //     File::makeDirectory($path, 0777, true, true);
        // }
        // $test['main_schedule'] = $calendar->main_schedule;
        // $test['sides_schedule'] = $calendar->sides_schedule;

        // $fileName1= 'main_schedule.json';
        // $fileName2 = 'sides_schedule.json';
        // File::put($path.'/'.$fileName1,$test['main_schedule']);
        // File::put($path.'/'.$fileName2,$test['sides_schedule']);
        $nutritional_info = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
        $visible_info = [];
        $filter_info = [];
        if ($nutritional_info) {
            $info = json_decode($nutritional_info->nutritional_info);
        } else {
            $info = json_decode(json_encode(config()->get('constants.nutritients')), false);
        }
        foreach ($info as $key => $value) {
            if ($value->mostrar == 1) {
                $filter_info[] = $value->id;
            }
            $visible_info[] = $value->id;

        }
        return view('calendario-planificador', compact('calendar', 'calendarios', 'visible_info', 'filter_info'));
    }
    public function viewUpdateCalendario(Request $request)
    {
        session(['calendario_id' => $request->calendario]);
        User::where('id', auth()->user()->id)->update(['calendario_id' => $request->calendario]);
        $url = url()->previous();
        $route = app('router')->getRoutes($url)->match(app('request')->create($url))->getName();
        if ($route == 'calender.listas') {
            return redirect()->route('calender.listas')->with(['info' => 'Se cambio la lista exitosamente']);
        }
        return redirect()->route('calendar.view')->with(['info' => 'Se cambio el calendario exitosamente']);
    }
    public function calendarioPdfv1(Request $request)
    {
        if ($request->ajax()) {
            $pdf_merger = new Merger();
            if ($request->session()->has('calendario_id')) {
                $calendar = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendar = Calendar::where('user_id', Auth::user()->id)->first();
            }
            $calendrio_recipient_email_address = auth()->user()->email;
            if ($request->has('calendrio_recipient_email_address')) {
                if (!empty($request->input('calendrio_recipient_email_address'))) {
                    $calendrio_recipient_email_address = $request->calendrio_recipient_email_address;
                }
            }
            if (auth()->user()->role_id == 3) {
                $nutritionals = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
                // if ($nutritionals) {
                //     $nutritionals_info = json_decode($nutritionals->nutritional_info);
                // } else {
                //     $nutritionals_info = config()->get('constants.nutritients');
                // }
                $taken_ingredientes = null;
                $categorias = null;
                $lista_ingredientes = null;
                $request_ingredients = null;
                $ingredients_count = null;

                $transformedNutriInfo = null;
                if (in_array(2, request()->export_param)) {
                    $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendar->id])->get();
                    $categorias = Categoria::orderBy('sort', 'ASC')->get();
                    $lista_ingredientes = ListaIngredientes::where('calendario_id', $calendar->id)->get();
                    $request_ingredients = json_decode($request->lista_ingredients);
                    $ingredients_count = (count((array) $request_ingredients) + $lista_ingredientes->count()) - count($taken_ingredientes);

                }
                $allNutris = [];
                if (in_array(4, request()->export_param)) {
                    $allNutris['day_1'] = $this->nutriInfo('day_1', $calendar->id, true);
                    $allNutris['day_2'] = $this->nutriInfo('day_2', $calendar->id, true);
                    $allNutris['day_3'] = $this->nutriInfo('day_3', $calendar->id, true);
                    $allNutris['day_4'] = $this->nutriInfo('day_4', $calendar->id, true);
                    $allNutris['day_5'] = $this->nutriInfo('day_5', $calendar->id, true);
                    $allNutris['day_6'] = $this->nutriInfo('day_6', $calendar->id, true);
                    $allNutris['day_7'] = $this->nutriInfo('day_7', $calendar->id, true);
                }
                $dataForPDF = array('nutri_info' => $allNutris,
                    'ingredients_count' => $ingredients_count,
                    'request_ingredients' => $request_ingredients,
                    'recipe_ingredients' => $request_ingredients,
                    'calendar' => $calendar,
                    'calendario' => $calendar,
                    'categorias' => $categorias,
                    'taken_ingredientes' => $taken_ingredientes,
                    'lista_ingredientes' => $lista_ingredientes);
                if (auth()->user()->theme == 1) {
                    //receta cover
                    if (!empty($request->receta_cover)):
                        $receta_cover_detail = Receta::where('id', $request->receta_cover)->firstOrFail();
                        if (!empty($receta_cover_detail->imagen_principal)):
                            $receta_cover_img_src = $receta_cover_detail->imagen_principal;
                            $dataForPDF['receta_cover_img_src'] = $receta_cover_img_src;
                        endif;
                        $receta_cover_pdf = PDF::loadView('pdf.classic.classic-rectia-cover', $dataForPDF)->setPaper('a4', 'potrait');
                    endif;
                    //snippet pdf
                    if (!empty($request->input('plantillas'))) {
                        $snippet_pdf = PDF::loadView('pdf.classic.classic-calendario-snippets', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //calendario pdf
                    if (in_array(1, request()->export_param)) {
                        $pdf = PDF::loadView('pdf.classic.classic-calendario-landscape', $dataForPDF)->setPaper('a4', 'landscape');
                    }
                    //nutri pdf
                    if (in_array(4, request()->export_param)) {
                        $nutri_pdf = PDF::loadView('pdf.classic.calendar-classic-nutri', $dataForPDF)->setPaper('a4', 'landscape');
                    }
                    //body potrait pdf
                    if (in_array(2, request()->export_param) || request()->receta) {
                        $pdf2 = PDF::loadView('pdf.classic.classic-calendario', $dataForPDF)->setPaper('a4', 'portrait');
                    }
                    //when body completes gathering files message
                    // if($pdf2)
                    // {
                    //     session(['export-intermediate-message' => 'Estamos juntando tus archivos']);//storing export intermediate message
                    // }
                    //merging
                    if (!empty($request->receta_cover)):
                        if (isset($receta_cover_pdf)):
                            $pdf_merger->addRaw($receta_cover_pdf->output()); //cover page pdf
                        endif;
                    endif;
                    if (!empty($request->input('plantillas'))) {
                        $pdf_merger->addRaw($snippet_pdf->output()); //plantillas pdf
                    }
                    if (in_array(1, request()->export_param)) {
                        $pdf_merger->addRaw($pdf->output()); //calendar pdf
                    }
                    if (in_array(4, request()->export_param)) {
                        $pdf_merger->addRaw($nutri_pdf->output()); //nutri pdf
                    }
                    if (in_array(2, request()->export_param) || request()->receta) {
                        $pdf_merger->addRaw($pdf2->output()); //body pdf
                    }
                    //creating pdf
                    $createdPdf = $pdf_merger->merge();
                    //after merging be patient message
                    // if($createdPdf)
                    // {
                    //     session(['export-intermediate-message' =>'Por favor se paciente']);//storing export intermediate message
                    // }
                    // dd(session()->get('export-intermediate-message'));
                    Storage::disk('local')->put($calendar->title . '.pdf', $createdPdf);
                } elseif (auth()->user()->theme == 2) {
                    //receta cover
                    if (!empty($request->receta_cover)):
                        $receta_cover_detail = Receta::where('id', $request->receta_cover)->firstOrFail();
                        if (!empty($receta_cover_detail->imagen_principal)):
                            $receta_cover_img_src = $receta_cover_detail->imagen_principal;
                            $dataForPDF['receta_cover_img_src'] = $receta_cover_img_src;
                        endif;
                        $receta_cover_pdf = PDF::loadView('pdf.modern.modern-rectia-cover', $dataForPDF)->setPaper('a4', 'potrait');
                    endif;
                    //snippet pdf
                    if (!empty($request->input('plantillas'))) {
                        $snippet_pdf = PDF::loadView('pdf.modern.modern-calendario-snippets', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //calendario pdf
                    if (in_array(1, request()->export_param)) {
                        $pdf = PDF::loadView('pdf.modern.modern-calendario-landscape', $dataForPDF)->setPaper('a4', 'landscape');
                    }
                    //nutri pdf
                    if (in_array(4, request()->export_param)) {
                        $nutri_pdf = PDF::loadView('pdf.modern.calendar-modern-nutri', $dataForPDF)->setPaper('a4', 'landscape');
                    }
                    //body potrait pdf
                    if (in_array(2, request()->export_param) || request()->receta) {
                        $pdf2 = PDF::loadView('pdf.modern.modern-calendario', $dataForPDF)->setPaper('a4', 'portrait');
                    }

                    //merging
                    if (!empty($request->receta_cover)):
                        if (isset($receta_cover_pdf)):
                            $pdf_merger->addRaw($receta_cover_pdf->output()); //cover page pdf
                        endif;
                    endif;
                    if (!empty($request->input('plantillas'))) {
                        $pdf_merger->addRaw($snippet_pdf->output()); //plantillas pdf
                    }
                    if (in_array(1, request()->export_param)) {
                        $pdf_merger->addRaw($pdf->output()); //calendar pdf
                    }
                    if (in_array(4, request()->export_param)) {
                        $pdf_merger->addRaw($nutri_pdf->output()); //nutri pdf
                    }
                    if (in_array(2, request()->export_param) || request()->receta) {
                        $pdf_merger->addRaw($pdf2->output()); //body pdf
                    }
                    //creating pdf
                    $createdPdf = $pdf_merger->merge();
                    Storage::disk('local')->put($calendar->title . '.pdf', $createdPdf);
                } else {
                    //receta cover
                    if (!empty($request->receta_cover)):
                        $receta_cover_detail = Receta::where('id', $request->receta_cover)->firstOrFail();
                        if (!empty($receta_cover_detail->imagen_principal)):
                            $receta_cover_img_src = $receta_cover_detail->imagen_principal;
                            $dataForPDF['receta_cover_img_src'] = $receta_cover_img_src;
                        endif;
                        $receta_cover_pdf = PDF::loadView('pdf.bold.bold-rectia-cover', $dataForPDF)->setPaper('a4', 'potrait');
                    endif;
                    //snippet pdf
                    if (!empty($request->input('plantillas'))) {
                        $snippet_pdf = PDF::loadView('pdf.bold.bold-calendario-snippets', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //calendario potrait pdf
                    if (in_array(1, request()->export_param)) {
                        $pdf_clendario_potrait = PDF::loadView('pdf.bold.bold-calendario-potrait', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //nutri pdf
                    if (in_array(4, request()->export_param)) {
                        $nutri_pdf = PDF::loadView('pdf.bold.calendar-bold-nutri', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //lista pdf
                    if (in_array(2, request()->export_param)) {
                        $lista_bold_pdf = PDF::loadView('pdf.bold.calendar-bold-lista', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //normal body potrait pdf (only receta is there)
                    if (request()->receta) {
                        $pdf = PDF::loadView('pdf.bold.bold-calendario', $dataForPDF)->setPaper('a4', 'potrait');
                    }
                    //merging
                    if (!empty($request->receta_cover)):
                        if (isset($receta_cover_pdf)):
                            $pdf_merger->addRaw($receta_cover_pdf->output()); //cover page pdf
                        endif;
                    endif;
                    if (!empty($request->input('plantillas'))) {
                        $pdf_merger->addRaw($snippet_pdf->output()); //plantillas pdf
                    }
                    if (in_array(1, request()->export_param)) {
                        $pdf_merger->addRaw($pdf_clendario_potrait->output()); //calendar potrait pdf
                    }
                    if (in_array(4, request()->export_param)) {
                        $pdf_merger->addRaw($nutri_pdf->output()); //nutri pdf
                    }
                    if (in_array(2, request()->export_param)) {
                        $pdf_merger->addRaw($lista_bold_pdf->output()); //lista pdf
                    }
                    if (request()->receta) {
                        $pdf_merger->addRaw($pdf->output()); //normal body potrait pdf
                    }
                    //creating pdf
                    $createdPdf = $pdf_merger->merge();
                    Storage::disk('local')->put($calendar->title . '.pdf', $createdPdf);
                }
                if ($request->has('calendrio_recipient_email_address')) {
                    if (filter_var($calendrio_recipient_email_address, FILTER_VALIDATE_EMAIL)) {
                        $data = array(
                            'email' => $calendrio_recipient_email_address,
                            'title' => "¡Tu plan de alimentación esta listo!",
                            'filename' => $calendar->title,
                            'current_time' => todaySpanishDay(),
                        );
                        if (!empty($request->input('plantillas'))) {
                            $data['plantillas'] = utf8_decode(urldecode(request()->plantillas));
                        } else {
                            $data['plantillas'] = '';
                        }
                        $content_pdf = Storage::disk('local')->get($calendar->title . '.pdf');
                        $mail_procedure = Mail::send('email.send-calendario-mail', $data, function ($message) use ($data, $content_pdf) {
                            $message->to($data["email"], $data["email"])
                                ->subject($data["title"])
                                ->attachData($content_pdf, $data['filename'] . ".pdf");
                        });
                        Mail::send('email.delivery-email', ['type' => 'Calendario', 'meal_type' => 'Plan de alimentación', 'to' => $data["email"],
                            'title' => $data['title'], 'current_time' => todaySpanishDay()],
                            function ($message) use ($data, $content_pdf) {
                                $message->to(auth()->user()->bemail, auth()->user()->bemail)
                                    ->subject('Tu plan de alimentación fue entregado')
                                    ->attachData($content_pdf, $data['filename'] . ".pdf");
                            });
                        $json_data['message'] = "Se envio por mail exitosamente";
                        $json_data['status'] = "success";
                    } else {
                        $json_data['message'] = "El correo electrónico del destinatario no es válido";
                        $json_data['status'] = "error";
                    }
                } else {
                    return response()->file(Storage::disk('local')->path($calendar->title . '.pdf'));
                }

            } else {
                $pdf = PDF::loadView('pdf.calendario-pdf', array('calendar' => $calendar))->setPaper('a4', 'landscape');
                return $pdf->stream($calendar->title . '.pdf');

            }
            // return view('pdf.bold-calendario-pdf', compact('calendar','request'));
            //
            echo json_encode($json_data);
            die();
        } else {
            dd("not allowed");
        }
    }
    public function create(Request $request)
    {
        $Calendar = Calendar::create([
            'user_id' => Auth::user()->id,
            'title' => $request->calendar_title,
            'main_schedule' => json_encode(config()->get('constants.schedule')),
            'main_leftovers' => json_encode(config()->get('constants.leftovers')),
            'main_servings' => json_encode(config()->get('constants.main_servings')),
            'main_racion' => json_encode(config()->get('constants.main_racion')),
            'sides_schedule' => json_encode(config()->get('constants.schedule')),
            'sides_leftovers' => json_encode(config()->get('constants.leftovers')),
            'sides_servings' => json_encode(config()->get('constants.sides_servings')),
            'sides_racion' => json_encode(config()->get('constants.sides_racion')),
            'labels' => json_encode(config()->get('constants.labels')),
        ]);
        if ($request->ajax()) {
            return response()->json(['success' => true, 'data' => $Calendar->id]);
        }
        session(['calendario_id' => $Calendar->id]);
        return redirect()->route('calendar.view')->with(['info' => 'Calendario creado']);
    }
    public function copy(Request $request, $id)
    {
        $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        $NewCalendar = Calendar::create([
            'user_id' => Auth::user()->id,
            'title' => $request->calendar_title,
            'main_schedule' => $calendar->main_schedule,
            'main_leftovers' => $calendar->main_leftovers,
            'main_servings' => $calendar->main_servings,
            'main_racion' => $calendar->main_racion,
            'sides_schedule' => $calendar->sides_schedule,
            'sides_leftovers' => $calendar->sides_leftovers,
            'sides_servings' => $calendar->sides_servings,
            'sides_racion' => $calendar->sides_racion,
            'labels' => $calendar->labels,
        ]);
        session(['calendario_id' => $NewCalendar->id]);
        if ($request->ajax()) {

            return response()->json(['success' => true, 'data' => $NewCalendar]);
        }

        return redirect()->route('calendar.view')->with(['info' => 'Calendario copiado']);
    }

    public function addRecipe(Request $request, $id)
    {
        if ($request->ajax()) {
            if ($request->has('update') && $request->update == 1) {
                $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
                if ($calendar && $request->recetaid && $request->old_mealnum && $request->old_daynum && $request->mealnum && $request->daynum) {

                    $old_mealnum = $request->old_mealnum;
                    $old_daynum = $request->old_daynum;

                    $mealnum = $request->mealnum;
                    $daynum = $request->daynum;

                    $cMains = json_decode($calendar->main_schedule, true);
                    $cSides = json_decode($calendar->sides_schedule, true);
                    $cMainServings = json_decode($calendar->main_servings, true);
                    $cMainLeftovers = json_decode($calendar->main_leftovers, true);

                    $cMains[$old_daynum][$old_mealnum] = null;
                    $cSides[$old_daynum][$old_mealnum] = null;

                    $cMains[$daynum][$mealnum] = intval($request->recetaid[0]);
                    if (isset($request->recetaid[1])) {
                        $cSides[$daynum][$mealnum] = intval($request->recetaid[1]);
                    }
                    if ($request->leftover) {
                        $cMainLeftovers[$daynum][$mealnum] = true;
                    }

                    $calendar->main_schedule = json_encode($cMains);
                    $calendar->main_servings = json_encode($cMainServings);
                    $calendar->main_leftovers = json_encode($cMainLeftovers);
                    $calendar->sides_schedule = json_encode($cSides);
                    $calendar->save();
                    $old_info = '';
                    $new_info = '';
                    if (auth()->user()->hasRole('professional')) {
                        $request->dayid = $daynum;
                        if ($old_daynum == $daynum) {
                            $new_info = $this->getNutritionInfo($request, $id)->original;
                        } else {
                            $new_info = $this->getNutritionInfo($request, $id)->original;
                            $request->dayid = $old_daynum;
                            $old_info = $this->getNutritionInfo($request, $id)->original;
                        }
                    }
                    return response()->json(['data' => $calendar, 'role_id' => Auth::user()->role_id, 'old_info' => $old_info, 'new_info' => $new_info, 'success' => true]);
                }
            } else if ($request->recetaid && $request->mealnum && $request->daynum) {
                $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
                if ($calendar) {
                    $mealnum = $request->mealnum;
                    $daynum = $request->daynum;
                    $cMains = json_decode($calendar->main_schedule, true);
                    $cMainServings = json_decode($calendar->main_servings, true);
                    $cMainLeftovers = json_decode($calendar->main_leftovers, true);
                    foreach ($daynum as $daykey) {
                        $cMains[$daykey][$mealnum] = intval($request->recetaid);
                        $cMainServings[$daykey][$mealnum] = intval($request->porciones);
                        if ($request->leftover) {
                            $cMainLeftovers[$daykey][$mealnum] = true;
                        }
                    }
                    $calendar->main_schedule = json_encode($cMains);
                    $calendar->main_servings = json_encode($cMainServings);
                    $calendar->main_leftovers = json_encode($cMainLeftovers);
                    $calendar->save();
                    $addedRecipe = Receta::select('id', 'titulo', 'slug', 'imagen_principal')->where('id', $request->recetaid)->first();
                    return response()->json(['message' => 'Calendario actualizado', 'data' => ['calendarId' => $calendar->id, 'role_id' => Auth::user()->role_id, 'recipe' => $addedRecipe, 'leftover' => $request->leftover, 'porcions' => $addedRecipe->getPorciones(), 'ingredients' => $addedRecipe->getIngredientes(),
                        'main_schedule' => $calendar->main_schedule, 'main_servings' => $calendar->main_servings, 'main_leftovers' => $calendar->main_leftovers], 'success' => true]);
                }
            }
        }
        // $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        // if ($calendar && $request->recetaid && $request->mealnum && $request->daynum) {
        //     $mealnum = $request->mealnum;
        //     $daynum = $request->daynum;
        //     $cMains = json_decode($calendar->main_schedule, true);
        //     $cMainServings = json_decode($calendar->main_servings, true);
        //     $cMainLeftovers = json_decode($calendar->main_leftovers, true);
        //     foreach ($daynum as $daykey) {
        //         $cMains[$daykey][$mealnum] = intval($request->recetaid);
        //         $cMainServings[$daykey][$mealnum] = intval($request->porciones);
        //         if ($request->leftover) {
        //             $cMainLeftovers[$daykey][$mealnum] = true;
        //         }
        //     }
        //     $calendar->main_schedule = json_encode($cMains);
        //     $calendar->main_servings = json_encode($cMainServings);
        //     $calendar->main_leftovers = json_encode($cMainLeftovers);
        //     $calendar->save();

        //     return redirect()->route('calendar.view')->with(['info' => 'Se han añadido recetas al calendario.']);
        // }
        // return redirect()->route('calendar.view')->with(['error' => 'No se actualizo el calendario.']);
    }
    public function addRecipecalender(Request $request)
    {
        // dd($request->all());
        $calendar = Calendar::where('id', $request->calenderio_id)->where('user_id', Auth::user()->id)->first();
        if ($calendar && $request->recetaid && $request->mealnum && $request->mealtype) {
            $mealnum = $request->mealnum;
            $daynum = ($request->daynum) ? $request->daynum : [];
            $day_arr = ['day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6', 'day_7'];
            $remove_days = array_diff($day_arr, $daynum);

            if ($request->mealtype == 'main') {
                $cMains = json_decode($calendar->main_schedule, true);
                $cMainServings = json_decode($calendar->main_servings, true);
                $cMainLeftovers = json_decode($calendar->main_leftovers, true);
                foreach ($daynum as $daykey) {
                    $cMains[$daykey][$mealnum] = intval($request->recetaid);
                    $cMainServings[$daykey][$mealnum] = intval($request->porciones);
                    if ($request->leftover) {
                        $cMainLeftovers[$daykey][$mealnum] = true;
                    } else {
                        $cMainLeftovers[$daykey][$mealnum] = false;
                    }
                }

                if ($remove_days) {
                    $cSides = json_decode($calendar->sides_schedule, true);
                    $cSideServings = json_decode($calendar->sides_servings, true);
                    $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                    foreach ($remove_days as $key => $value) {
                        if ($cMains[$value][$mealnum] == intval($request->recetaid)) {
                            $cMains[$value][$mealnum] = null;
                            $cMainServings[$value][$mealnum] = 1;
                            $cMainLeftovers[$value][$mealnum] = null;
                            $cSides[$value][$mealnum] = null;
                            $cSideServings[$value][$mealnum] = 1;
                            $cSideLeftovers[$value][$mealnum] = null;
                        }
                    }
                    $calendar->sides_schedule = json_encode($cSides);
                    $calendar->sides_servings = json_encode($cSideServings);
                    $calendar->sides_leftovers = json_encode($cSideLeftovers);
                }
                $calendar->main_schedule = json_encode($cMains);
                $calendar->main_servings = json_encode($cMainServings);
                $calendar->main_leftovers = json_encode($cMainLeftovers);
            } else {
                $cSides = json_decode($calendar->sides_schedule, true);
                $cSideServings = json_decode($calendar->sides_servings, true);
                $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                foreach ($daynum as $daykey) {
                    $cSides[$daykey][$mealnum] = intval($request->recetaid);
                    $cSideServings[$daykey][$mealnum] = intval($request->porciones);
                    if ($request->leftover) {
                        $cSideLeftovers[$daykey][$mealnum] = true;
                    } else {
                        $cSideLeftovers[$daykey][$mealnum] = false;
                    }
                }
                if ($remove_days) {
                    foreach ($remove_days as $key => $value) {
                        if ($cSides[$value][$mealnum] == intval($request->recetaid)) {
                            $cSides[$value][$mealnum] = null;
                            $cSideServings[$value][$mealnum] = 1;
                            $cSideLeftovers[$value][$mealnum] = null;
                        }
                    }
                }
                $calendar->sides_schedule = json_encode($cSides);
                $calendar->sides_servings = json_encode($cSideServings);
                $calendar->sides_leftovers = json_encode($cSideLeftovers);
            }
            session(['calendario_id' => $calendar->id]);
            $calendar->save();

            return response()->json(['message' => 'Se han añadido recetas al calendario.']);
        }
        return response()->json(['message' => 'No se actualizo el calendario.']);
    }

    public function checkDayscalender(Request $request)
    {
        $calendar = Calendar::where('id', $request->calenderio_id)->where('user_id', Auth::user()->id)->first();
        $flag = false;
        if ($request->typeval == 'main' || empty($request->typeval)) {
            $cData = json_decode($calendar->main_schedule, true);
        } else {
            $cData = json_decode($calendar->sides_schedule, true);
            $cMains = json_decode($calendar->main_schedule, true);

            $cRecpdays = array();
            foreach ($cMains as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if ($key1 == $request->tiempos_id && $value1 == $request->recetaid) {
                        $cRecpdays[] = $key;
                    }
                }
            }
            if ($cRecpdays) {
                $flag = true;
            }
        }

        $return = [];
        foreach ($cData as $key => $value) {
            foreach ($value as $key1 => $value1) {
                if ($key1 == $request->tiempos_id && $value1 == $request->recetaid) {
                    $return[] = $key;
                }
            }
        }
        if ($flag == false && $request->typeval == 'side') {
            $day_arr = ['day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6', 'day_7'];
            return response()->json($day_arr);
        }
        return response()->json($return);
    }
    public function updateRecipe(Request $request, $id)
    {
        // dd($request->all());
        $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        if ($calendar && $request->recetaid && $request->mealnum && $request->mealtype) {
            $mealnum = $request->mealnum;
            $updaynum = $request->update_day;
            $daynum = ($request->daynum) ? $request->daynum : [];
            $day_arr = ['day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6', 'day_7'];
            $remove_days = array_diff($day_arr, $daynum);

            if ($request->mealtype == 'main') {
                $cMains = json_decode($calendar->main_schedule, true);
                $cMainServings = json_decode($calendar->main_servings, true);
                $cMainLeftovers = json_decode($calendar->main_leftovers, true);
                foreach ($daynum as $daykey) {
                    $cMains[$daykey][$mealnum] = intval($request->recetaid);
                    $cMainServings[$updaynum][$mealnum] = intval($request->porciones);
                    if ($request->leftover) {
                        $cMainLeftovers[$updaynum][$mealnum] = true;
                    } else {
                        $cMainLeftovers[$updaynum][$mealnum] = false;
                    }
                }

                if ($remove_days) {
                    $cSides = json_decode($calendar->sides_schedule, true);
                    $cSideServings = json_decode($calendar->sides_servings, true);
                    $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                    foreach ($remove_days as $key => $value) {
                        if ($cMains[$value][$mealnum] == intval($request->recetaid)) {
                            $cMains[$value][$mealnum] = null;
                            $cMainServings[$value][$mealnum] = 1;
                            $cMainLeftovers[$value][$mealnum] = null;
                            $cSides[$value][$mealnum] = null;
                            $cSideServings[$value][$mealnum] = 1;
                            $cSideLeftovers[$value][$mealnum] = null;
                        }
                    }
                    $calendar->sides_schedule = json_encode($cSides);
                    $calendar->sides_servings = json_encode($cSideServings);
                    $calendar->sides_leftovers = json_encode($cSideLeftovers);
                }
                $calendar->main_schedule = json_encode($cMains);
                $calendar->main_servings = json_encode($cMainServings);
                $calendar->main_leftovers = json_encode($cMainLeftovers);
            } else {
                $cSides = json_decode($calendar->sides_schedule, true);
                $cSideServings = json_decode($calendar->sides_servings, true);
                $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                foreach ($daynum as $daykey) {
                    $cSides[$daykey][$mealnum] = intval($request->recetaid);
                    $cSideServings[$updaynum][$mealnum] = intval($request->porciones);
                    if ($request->leftover) {
                        $cSideLeftovers[$updaynum][$mealnum] = true;
                    } else {
                        $cSideLeftovers[$updaynum][$mealnum] = false;
                    }
                }
                if ($remove_days) {
                    foreach ($remove_days as $key => $value) {
                        if ($cSides[$value][$mealnum] == intval($request->recetaid)) {
                            $cSides[$value][$mealnum] = null;
                            $cSideServings[$value][$mealnum] = intval($request->porciones);
                            $cSideLeftovers[$value][$mealnum] = null;
                        }
                    }
                    //$calendar->sides_schedule = json_encode($cSides);
                    //$calendar->sides_servings = json_encode($cSideServings);
                    //$calendar->sides_leftovers = json_encode($cSideLeftovers);
                }
                $calendar->sides_schedule = json_encode($cSides);
                $calendar->sides_servings = json_encode($cSideServings);
                $calendar->sides_leftovers = json_encode($cSideLeftovers);
            }
            $calendar->save();
            $addedRecipe = Receta::select('id', 'titulo', 'slug', 'imagen_principal')->where('id', $request->recetaid)->first();
            return response()->json(['message' => 'Calendario actualizado', 'data' => ['calendarId' => $calendar->id, 'role_id' => Auth::user()->role_id,
                'recipe' => $addedRecipe, 'leftover' => $request->leftover, 'porcions' => $addedRecipe->getPorciones(), 'ingredients' => $addedRecipe->getIngredientes(),
                'main_schedule' => $calendar->main_schedule, 'main_servings' => $calendar->main_servings, 'main_racion' => $calendar->main_racion, 'main_leftovers' => $calendar->main_leftovers,
                'sides_schedule' => $calendar->sides_schedule, 'sides_servings' => $calendar->sides_servings, 'sides_racion' => $calendar->sides_racion, 'sides_leftovers' => $calendar->sides_leftovers], 'success' => true]);
            // return redirect()->route('calendar.view')->with(['info' => 'Calendario actualizado']);
        }
        return redirect()->route('calendar.view')->with(['error' => 'No se actualizo el calendario.']);
    }

    public function removeRecipe(Request $request, $id)
    {
        $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        if ($calendar && $request->mealnum && $request->daynum && $request->mealtype) {
            $deletedRecipes = [];
            $mealnum = $request->mealnum;
            $daynum = $request->daynum;
            if ($request->mealtype == 'main') {
                $cMains = json_decode($calendar->main_schedule, true);
                $cSides = json_decode($calendar->sides_schedule, true);
                $deletedRecipes = [$cMains[$daynum][$mealnum], $cSides[$daynum][$mealnum]];
                $cMainServings = json_decode($calendar->main_servings, true);
                $cMainLeftovers = json_decode($calendar->main_leftovers, true);
                $cMains[$daynum][$mealnum] = null;
                $cMainServings[$daynum][$mealnum] = 1;
                $cMainLeftovers[$daynum][$mealnum] = null;
                $calendar->main_schedule = json_encode($cMains);
                $calendar->main_servings = json_encode($cMainServings);
                $calendar->main_leftovers = json_encode($cMainLeftovers);

                //side
                $cSideServings = json_decode($calendar->sides_servings, true);
                $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                $cSides[$daynum][$mealnum] = null;
                $cSideServings[$daynum][$mealnum] = 1;
                $cSideLeftovers[$daynum][$mealnum] = null;
                $calendar->sides_schedule = json_encode($cSides);
                $calendar->sides_servings = json_encode($cSideServings);
                $calendar->sides_leftovers = json_encode($cSideLeftovers);
            } else {
                //side
                $cSides = json_decode($calendar->sides_schedule, true);
                $cSideServings = json_decode($calendar->sides_servings, true);
                $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                $deletedRecipes = [$cSides[$daynum][$mealnum]];
                $cSides[$daynum][$mealnum] = null;
                $cSideServings[$daynum][$mealnum] = 1;
                $cSideLeftovers[$daynum][$mealnum] = null;
                $calendar->sides_schedule = json_encode($cSides);
                $calendar->sides_servings = json_encode($cSideServings);
                $calendar->sides_leftovers = json_encode($cSideLeftovers);

            }

            $calendar->save();

            return response()->json(['type' => 'success', 'data' => ['deletedRecipes' => $deletedRecipes, 'calendar' => $calendar, 'role_id' => Auth::user()->role_id], 'message' => 'La receta se ha eliminado del calendario.']);
        }
        return response()->json(['type' => 'error', 'message' => 'No se actualizo el calendario.']);
    }

    public function leftoverRecipe(Request $request, $id)
    {
        $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        if ($calendar && $request->mealnum && $request->daynum && $request->mealtype) {
            $mealnum = $request->mealnum;
            $daynum = $request->daynum;
            if ($request->mealtype == 'main') {
                $cMainLeftovers = json_decode($calendar->main_leftovers, true);
                $cMainLeftovers[$daynum][$mealnum] = $request->recpisleftover == 1 ? 0 : 1;
                $calendar->main_leftovers = json_encode($cMainLeftovers);
            } else {
                $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
                $cSideLeftovers[$daynum][$mealnum] = $request->recpisleftover == 1 ? 0 : 1;
                $calendar->sides_leftovers = json_encode($cSideLeftovers);
            }
            $calendar->save();

            return response()->json(['type' => 'success', 'message' => 'Calendario actualizado']);
        }
        return response()->json(['type' => 'error', 'message' => 'No se actualizo el calendario.']);
    }

    public function update(Request $request, $id)
    {
        $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        if ($calendar) {
            $calendar->title = $request->calendar_title;
            $calendar->save();
            return response()->json(['type' => 'success', 'message' => 'Calendario actualizado']);
        }
        return response()->json(['type' => 'error', 'message' => 'No se actualizo el calendario.']);

    }

    public function updateLabels(Request $request, $id)
    {
        $calendar = Calendar::where('id', $id)->where('user_id', Auth::user()->id)->first();
        if ($calendar) {
            $cLabels = json_decode($calendar->labels, true);
            if ($request->label_type == 'days') {
                $cLabels[$request->label_type][$request->label_name] = $request->days;
            } else {
                $cLabels[$request->label_type][$request->label_name] = $request->meals;
            }
            $calendar->labels = json_encode($cLabels);
            $calendar->save();
            return response()->json(['type' => 'success', 'message' => 'Calendario actualizado']);
            // return redirect()->route('calendar.view')->with(['info' => 'Calendario actualizado']);
        }
        return response()->json(['type' => 'error', 'message' => 'No se actualizo el calendario.']);
        // return redirect()->route('calendar.view')->with(['error' => 'No se actualizo el calendario.']);
    }

    public function delete(Request $request, $id)
    {
        Calendar::where('id', $id)->where('user_id', Auth::user()->id)->delete();

        return redirect()->route('calendar.view')->with(['error' => 'Calendario eliminado']);
    }

    public function getCalenderModal(Request $request)
    {
        $recetas = Receta::orderBy('free', 'desc')->orderBy('id', 'desc')->get();

        $calender_modal = view('calendar-modal', compact('recetas'))->render();
        return response()->json(['success' => true, 'role_id' => Auth::user()->role_id, 'calender_modal' => $calender_modal]);

    }

    public function updateRacion(Request $request)
    {
        $calendar = Calendar::where('id', $request->calendar_id)->where('user_id', Auth::user()->id)->first();
        if ($calendar) {
            if ($request->meal_type == 'main') {
                $cRacion = json_decode($calendar->main_racion, true);
                $cServing = json_decode($calendar->main_servings, true);
                $cRacion[$request->day_id][$request->meal_id] = $request->calendar_scale;
                $cServing[$request->day_id][$request->meal_id] = $request->serving;
                $calendar->main_racion = json_encode($cRacion);
                $calendar->main_servings = json_encode($cServing);
            } else {
                $cRacion = json_decode($calendar->sides_racion, true);
                $cServing = json_decode($calendar->sides_servings, true);
                $cRacion[$request->day_id][$request->meal_id] = $request->calendar_scale;
                $cServing[$request->day_id][$request->meal_id] = $request->serving;
                $calendar->sides_racion = json_encode($cRacion);
                $calendar->sides_servings = json_encode($cServing);
            }
            $calendar->save();
            return response()->json(['success' => true, 'role_id' => Auth::user()->role_id, 'message' => 'Calendario actualizado']);
            //return redirect()->route('calendar.view')->with(['info' => 'Calendario actualizado']);
        }
        return response()->json(['success' => true, 'message' => 'No se actualizo el calendario.']);
    }

    public function getNutritionInfo(Request $request, $id)
    {
        $daykey = $request->dayid;
        if (isset($request->modal)) {
            $nutriInfo = $this->nutriInfo($daykey, $id);
            $recipes_list = $nutriInfo[0];
            $calendar = $nutriInfo[1];
            $response = $nutriInfo[2];
            $nutrition_modal = view('nutritional-info', ['recipes_list' => $recipes_list, 'calendar' => $calendar, 'daykey' => $daykey, 'data' => $response['data'], 'percentage_total' => $response['percentage_total']])->render();
            return response()->json(['success' => true, 'day_id' => $daykey, 'nutrition_modal' => $nutrition_modal]);

        } else {
            $nutriInfo = $this->nutriInfo($daykey, $id, true);
        }

        //return;
        // $nutritionBottom = view('partials/sub_templates/nutrient_bottom', ['data' => $nutriInfo])->render();
        return response()->json(['success' => true, 'day_id' => $daykey, 'nutrition' => $nutriInfo]);
    }
    public function nutriInfo($daykey, $id, $forBottomRow = false)
    {
        $calendar = Calendar::where('id', $id)->first();
        $nutritional_info = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
        $visible_info = [];
        $filter_info = [];
        if ($nutritional_info) {
            $info = json_decode($nutritional_info->nutritional_info);
        } else {
            $info = json_decode(json_encode(config()->get('constants.nutritients')), false);
        }
        foreach ($info as $key => $value) {
            if ($value->mostrar == 1) {
                $filter_info[] = $value->id;
            }
            $visible_info[] = $value->id;

        }
        $recipes_list = Receta::all()->sortBy("free");
        if ($forBottomRow) {
            $response = getDayNutritionData($daykey, $calendar, $visible_info, $filter_info);
            return $response;
        } else {
            $response = getDayNutritionForModal($daykey, $calendar, $visible_info, $filter_info);
            return [$recipes_list, $calendar, $response];
        }
    }

    public function transformNutriInfo($allNutris)
    {
        $nutritional_info = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
        $filter_info = [];
        if ($nutritional_info) {
            $info = json_decode($nutritional_info->nutritional_info);
        } else {
            $info = json_decode(json_encode(config()->get('constants.nutritients')), false);
        }
        foreach ($info as $key => $value) {
            if ($value->mostrar == 1) {
                $filter_info[] = $value->id;
            }
        }
        $nutriToBeRendered = [];
        foreach ($allNutris as $dayKey => $value) {
            $arrData = $value['data'][$dayKey];
            $perData = $value['percentage_total'];
            foreach ($arrData as $obj) {
                $cantidad = $this->getCantidadForNutrients($obj);
                $porcentaje = null;
                if (in_array($obj['id'], $filter_info)) {
                    if ($obj['id'] == '96' || $obj['id'] == '97' || $obj['id'] == '99') {

                        if ($perData[$dayKey]) {
                            $porcentaje = $cantidad / $perData[$dayKey] * 100;
                        } else {
                            $porcentaje = $cantidad / 100 * 100;
                        }
                    }
                    $nutriToBeRendered[$dayKey][] = [$obj['id'], $obj['nombre'], $obj['unidad_medida'], $cantidad, $porcentaje];
                }
            }
        }
        return $nutriToBeRendered;
    }
    public function getCantidadForNutrients($dataValue)
    {
        $cantidad = $dataValue['cantidad'] * $dataValue['racion'];
        if (isset($dataValue['repeat'])) {
            foreach ($dataValue['repeat'] as $key => $repeat) {
                $cantidad += $repeat['cantidad'] * $repeat['racion'];
                if (isset($repeat['sub_nutrientes'])) {
                    foreach ($repeat['sub_nutrientes'] as $key => $sub_nutrientes) {
                        $cantidad += $sub_nutrientes['cantidad'] * $repeat['sub_racion'];
                    }
                }
            }
        }
        if (isset($dataValue['sub_nutrientes'])) {
            foreach ($dataValue['sub_nutrientes'] as $key => $sub_nutrientes) {
                $cantidad += $sub_nutrientes['cantidad'] * $dataValue['sub_racion'];
            }
        }
        return $cantidad;
    }

    public function calendarioPdf(Request $request)
    {
        $disk = 'pdfs';//env('ENV_APP') == 'local' ? 'local' : 'gcs';

        if ($request->ajax()) {
            if ($request->session()->has('calendario_id')) {
                $calendar = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendar = Calendar::where('user_id', Auth::user()->id)->first();
            }            
            if ($request->has('receta_count')) {
                $recetas = $this->getRecipesListToExport($calendar, $request->receta);
                echo json_encode($recetas);
                die();
            }
            $calendrio_recipient_email_address = auth()->user()->email;
            if ($request->has('calendrio_recipient_email_address')) {
                if (!empty($request->input('calendrio_recipient_email_address'))) {
                    $calendrio_recipient_email_address = $request->calendrio_recipient_email_address;
                }
            }
            if (auth()->user()->role_id == 3) {
                //gather data
                $pdf_merger = new Merger();
                $calName = strtolower(str_replace(' ', '', $calendar->title));
                $partialFileName = Auth::user()->id . '-' . $calName . '-partial-receta';
                if ($request->has('export_stitch')) {

                    $exists = Storage::disk($disk)->exists($partialFileName . '.pdf');
                    if ($exists) {
                        $pdf_merger->addFile(Storage::disk($disk)->path($partialFileName . '.pdf'));
                    } else {
                        //echo 'NAA'.$partialFileName;
                    }
                    for ($i = 0; $i < $request->total; $i++) {
                        $exists = Storage::disk($disk)->exists($partialFileName . $i . '.pdf');
                        if ($exists) {
                            $pdf_merger->addFile(Storage::disk($disk)->path($partialFileName . $i . '.pdf'));
                        }else{
                            $json_data['message'] = "File error ".$partialFileName;
                            $json_data['status'] = "error";
                            echo json_encode($json_data);
                            die();
                        }
                    }
                    $createdPdf = $pdf_merger->merge();
                    $bindFileName = Auth::user()->id . '-' . $calName .'-'.auth()->user()->theme . '.pdf';
                    Storage::disk('gcs')->put($bindFileName, $createdPdf);
                    for ($i = 0; $i < $request->total; $i++) {
                        $exists = Storage::disk($disk)->exists($partialFileName . $i . '.pdf');
                        if ($exists) {
                           // Storage::disk($disk)->delete($partialFileName . $i . '.pdf');
                        }
                    }
                    $fileURL = 'https://storage.googleapis.com/hmartina.appspot.com/'.$bindFileName;
                    if ($request->has('calendrio_recipient_email_address')) {
                        if (filter_var($calendrio_recipient_email_address, FILTER_VALIDATE_EMAIL)) {
                            $data = array(
                                'email' => $calendrio_recipient_email_address,
                                'title' => "¡Tu plan de alimentación esta listo!",
                                // 'filename' => Auth::user()->id . '-' . $calName . '.pdf',
                                'fileURL'=>$fileURL,
                                'current_time' => todaySpanishDay(),
                            );
                            if (!empty($request->input('plantillas'))) {
                                $data['plantillas'] = utf8_decode(urldecode(request()->plantillas));
                            } else {
                                $data['plantillas'] = '';
                            }
                            // $content_pdf = Storage::disk($disk)->get(Auth::user()->id . '-' . $calName . '.pdf');
                            $mail_procedure = Mail::send('email.send-calendario-mail', $data, function ($message) use ($data) {
                                $message->to($data["email"], $data["email"])
                                    ->subject($data["title"]);
                                    // ->attachData($content_pdf, $data['filename'] . ".pdf");
                            });
                            Mail::send('email.delivery-email', ['type' => 'Calendario', 'meal_type' => 'Plan de alimentación', 'to' => $data["email"],
                                'title' => $data['title'],'fileURL'=>$fileURL, 'current_time' => todaySpanishDay()],
                                function ($message) use ($data) {
                                    $message->to(auth()->user()->bemail, auth()->user()->bemail)
                                        ->subject('Tu plan de alimentación fue entregado');
                                        // ->attachData($content_pdf, $data['filename'] . ".pdf");
                                });
                            $json_data['message'] = "Se envio por mail exitosamente";
                            $json_data['status'] = "success";
                        } else {
                            $json_data['message'] = "El correo electrónico del destinatario no es válido";
                            $json_data['status'] = "error";
                        }
                        echo json_encode($json_data);
                        die();
                    } else {
                        $json_data["filename"] = $bindFileName;
                        $json_data['message'] = $fileURL ;
                        $json_data['status'] = 'success';
                        echo json_encode($json_data);
                        die();
                        // return response()->file(Storage::disk($disk)->path(Auth::user()->id . '-' . $calName . '.pdf'));
                    }
                }
                $dataForPDF = ['calendar' => $calendar];
                if (!$request->receta) {
                    $dataForPDF = $this->gatherPdfData($request, $calendar);
                }

                if (!empty($request->receta_cover)) {
                    $receta_cover_detail = Receta::where('id', $request->receta_cover)->firstOrFail();
                    if (!empty($receta_cover_detail->imagen_principal)) {
                        $receta_cover_img_src = $receta_cover_detail->imagen_principal;
                        $dataForPDF['receta_cover_img_src'] = $receta_cover_img_src;
                    }
                }
                if (request()->receta) {
                    $fileName = $partialFileName . request()->partialRecetaBatch;
                } else {
                    $fileName = $partialFileName;
                }
                if (auth()->user()->theme == 1) {
                    //basic theme
                    if (request()->receta) {
                        $basicPdf = ['recipeLista' => [true, 'pdf.classic.classic-calendario', 'portrait']];
                    } else {
                        $basicPdf = [
                            'recetaCover' => [!empty($request->receta_cover), 'pdf.classic.classic-rectia-cover', 'portrait'],
                            'snippets' => [!empty($request->input('plantillas')), 'pdf.classic.classic-calendario-snippets', 'portrait'],
                            'calendar' => [in_array(1, request()->export_param), 'pdf.classic.classic-calendario-landscape', 'landscape'],
                            'nutri' => [in_array(4, request()->export_param), 'pdf.classic.calendar-classic-nutri', 'landscape'],
                            'lista' => [in_array(2, request()->export_param) ? 'y' : 'n', 'pdf.classic.calendar-classic-lista', 'portrait'],
                            'recipe' => [request()->receta, 'pdf.classic.classic-calendario', 'portrait'],
                        ];
                    }
                    $this->createPdfs($basicPdf, $dataForPDF, $fileName);
                } elseif (auth()->user()->theme == 2) {
                    //modern
                    if (request()->receta) {
                        $modernPdf = ['recipeLista' => ['y', 'pdf.modern.modern-calendario', 'portrait']];
                    } else {
                        $modernPdf = [
                            'recetaCover' => [!empty($request->receta_cover) ? 'y' : 'n', 'pdf.modern.modern-rectia-cover', 'portrait'],
                            'snippets' => [!empty($request->input('plantillas')) ? 'y' : 'n', 'pdf.modern.modern-calendario-snippets', 'portrait'],
                            'calendar' => [in_array(1, request()->export_param) ? 'y' : 'n', 'pdf.modern.modern-calendario-landscape', 'landscape'],
                            'nutri' => [in_array(4, request()->export_param) ? 'y' : 'n', 'pdf.modern.calendar-modern-nutri', 'landscape'],
                            'lista' => [in_array(2, request()->export_param) ? 'y' : 'n', 'pdf.modern.calendar-modern-lista', 'portrait'],
                            'recipe' => [request()->receta ? 'y' : 'n', 'pdf.modern.modern-calendario', 'portrait'],
                        ];
                    }
                    $this->createPdfs($modernPdf, $dataForPDF, $fileName);
                } else {
                    //pro theme
                    if (request()->receta) {
                        $boldPdf = ['recipeLista' => ['y', 'pdf.bold.bold-calendario', 'portrait']];
                    } else {
                        $boldPdf = [
                            'recetaCover' => [!empty($request->receta_cover) ? 'y' : 'n', 'pdf.bold.bold-rectia-cover', 'portrait'],
                            'snippets' => [!empty($request->input('plantillas')) ? 'y' : 'n', 'pdf.bold.bold-calendario-snippets', 'portrait'],
                            'calendar' => [in_array(1, request()->export_param) ? 'y' : 'n', 'pdf.bold.bold-calendario-potrait', 'portrait'],
                            'nutri' => [in_array(4, request()->export_param) ? 'y' : 'n', 'pdf.bold.calendar-bold-nutri', 'portrait'],
                            'lista' => [in_array(2, request()->export_param) ? 'y' : 'n', 'pdf.bold.calendar-bold-lista', 'portrait'],
                            'recipe' => [ request()->receta ? 'y' : 'n', 'pdf.bold.bold-calendario', 'portrait'],
                        ];
                    }
                    $this->createPdfs($boldPdf, $dataForPDF, $fileName);
                }
                $fileAdded = Storage::disk($disk)->path($fileName . '.pdf');
                $exists = Storage::disk($disk)->exists($fileName . '.pdf');
                // else{
                echo json_encode(['status' => 'success', 'fileadded' => $fileAdded, 'exists' => $exists]);
                die();
                //}
            } else {
                $pdf = PDF::loadView('pdf.calendario-pdf', array('calendar' => $calendar))->setPaper('a4', 'landscape');
                return $pdf->stream($calendar->title . '.pdf');
            }
            // echo json_encode($json_data);
            die();
        } else {
            dd("not allowed");
        }
    }
    public function createPdfs($config, $dataForPDF, $fileName)
    {
        $disk = 'pdfs';//env('ENV_APP') == 'local' ? 'local' : 'gcs';
        $allPdfs = [];
        $pdfs = [];
        $pdf_merger = new Merger();
        foreach ($config as $key => $pdf) {
            if ($pdf[0] == 'y') {
                if(request()->has('partialRecetaBatch')){
                    $dataForPDF = $this->recipesPdfData(request()->receta,request()->servings);
                }
                $pdfs[$key] = PDF::loadView($pdf[1], $dataForPDF)->setPaper('a4', $pdf[2]);
            }
        }
        foreach ($pdfs as $key => $pdfa) {
            $pdf_merger->addRaw($pdfa->output());
        }
        $createdPdf = $pdf_merger->merge();
        Storage::disk($disk)->put($fileName . '.pdf', $createdPdf);
    }

    public function gatherPdfData($request, $calendar)
    {
        $nutritionals = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
        $taken_ingredientes = null;
        $categorias = null;
        $lista_ingredientes = null;
        $request_ingredients = null;
        $ingredients_count = null;

        $transformedNutriInfo = null;
        if (in_array(2, request()->export_param)) {
            $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendar->id])->get();
            $categorias = Categoria::orderBy('sort', 'ASC')->get();
            $lista_ingredientes = ListaIngredientes::where('calendario_id', $calendar->id)->get();
            $request_ingredients = json_decode($request->lista_ingredients);
            $ingredients_count = (count((array) $request_ingredients) + $lista_ingredientes->count()) - count($taken_ingredientes);

        }
        $allNutris = [];
        if (in_array(4, request()->export_param)) {
            $allNutris['day_1'] = $this->nutriInfo('day_1', $calendar->id, true);
            $allNutris['day_2'] = $this->nutriInfo('day_2', $calendar->id, true);
            $allNutris['day_3'] = $this->nutriInfo('day_3', $calendar->id, true);
            $allNutris['day_4'] = $this->nutriInfo('day_4', $calendar->id, true);
            $allNutris['day_5'] = $this->nutriInfo('day_5', $calendar->id, true);
            $allNutris['day_6'] = $this->nutriInfo('day_6', $calendar->id, true);
            $allNutris['day_7'] = $this->nutriInfo('day_7', $calendar->id, true);
        }
        $dataForPDF = array('nutri_info' => $allNutris,
            'ingredients_count' => $ingredients_count,
            'request_ingredients' => $request_ingredients,
            'recipe_ingredients' => $request_ingredients,
            'calendar' => $calendar,
            'calendario' => $calendar,
            'categorias' => $categorias,
            'taken_ingredientes' => $taken_ingredientes,
            'lista_ingredientes' => $lista_ingredientes);
        return $dataForPDF;
    }
    public function recipesPdfData($recetaIDs,$servings)
    {
        $cRecipes = array();
        $cRecps = Receta::whereIn('id', $recetaIDs)->get();
        foreach ($cRecps as $cRecipe) {
            $cRecipes[$cRecipe->id] = $cRecipe;
        }
        return ['cRecipes'=>$cRecipes,'cRecpServings'=>$servings];
    }
    public function getRecipesListToExport($calendar, $receta){
        $cMains = json_decode($calendar->main_schedule, true);
        $cSides = json_decode($calendar->sides_schedule, true);
        $cMainLeftovers = json_decode($calendar->main_leftovers, true);
        $cMainServings = json_decode($calendar->main_servings, true);
        $cSideLeftovers = json_decode($calendar->sides_leftovers, true);
        $cSideServings = json_decode($calendar->sides_servings, true);
        $subRecipes = array();
        $cRecpIds = array();
        $cRecpekeys = array();
        $cRecpServings = array();
        foreach ($cMains as $day => $cMain) {
            foreach ($cMain as $key => $cRecpId) {
                if ($cRecpId && !in_array($cRecpId, $cRecpIds)) {
                    if ($receta && in_array($cRecpId, $receta) && !in_array($cRecpId, $subRecipes)) {
                        $cRecpIds[] = $cRecpId;
                        $cRecpServings[$cRecpId] = $cMainServings[$day][$key];
                        $subrecipes = DB::table('receta_instruccion_receta')
                            ->selectRaw('subreceta_id')
                            ->where('subreceta_id', '<>', null)
                            ->where('receta_id', '=', $cRecpId)->get()->all();
                        $sr = array_map(function ($s) {
                            $s = (array) $s;
                            $subrecetaId = $s['subreceta_id'];
                            $exists = Receta::where('id', $subrecetaId)->exists();
                            return $exists ? $subrecetaId : null;
                        }, $subrecipes);
                        $subRecipes[] = [...$sr];
                    }
                }
            }
        }

        foreach ($cSides as $day => $cSide) {
            foreach ($cSide as $key => $cRecpId) {
                if ($cRecpId && !in_array($cRecpId, $cRecpIds)) {
                    if ($receta && in_array($cRecpId, $receta) && !in_array($cRecpId, $subRecipes)) {
                        $cRecpIds[] = $cRecpId;
                        $cRecpServings[$cRecpId] = $cSideServings[$day][$key];
                        $subrecipes = DB::table('receta_instruccion_receta')
                            ->selectRaw('subreceta_id')
                            ->where('subreceta_id', '<>', null)
                            ->where('receta_id', '=', $cRecpId)->get()->all();
                        $sr = array_map(function ($s) {
                            $s = (array) $s;
                            $subrecetaId = $s['subreceta_id'];
                            $exists = Receta::where('id', $subrecetaId)->exists();
                            return $exists ? $subrecetaId : null;
                        }, $subrecipes);
                        $subRecipes[] = [...$sr];
                    }
                }
            }
        }
        $subRecipes = array_unique(array_flatten($subRecipes));
        $subRecipes = array_filter($subRecipes, function ($value) {
            return $value !== null;
        });
        $subCRecps = Receta::whereIn('id', $subRecipes)->get();
        foreach($subCRecps as $cRecipe) {
            $cRecpServings[$cRecipe->id] = $cRecipe->getPorciones()['cantidad'];
        }
        $json_data=[];
        $json_data['recetas'] = array_merge($cRecpIds,$subRecipes);
        $json_data['servings'] = $cRecpServings;
        $json_data['status'] = 'success';
        return $json_data;
    }
}