<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Calendar;
use App\Models\Categoria;
use App\Models\Comment;
use App\Models\Equivalence;
use App\Models\Ingrediente;
use App\Models\ListaIngredientes;
use App\Models\Nutriente;
use App\Models\NutrientType;
use App\Models\Plan;
use App\Models\Reaction;
use App\Models\Receta;
use App\Models\RecetaInstruccionReceta;
use App\Models\Tag;
use App\Notifications\CommentAnsweredNotification;
use App\Notifications\CommentAddedNotification;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\User;
use Mail;

class RecetasController extends BaseController
{
    public function recetas()
    {
        // $key = \config('services.stripe.secret');
        // $stripe = new \Stripe\StripeClient($key);
        // $in = $stripe->invoices->retrieve(
        //     'in_1KjHsoDdKJhAlBDMCo1HsSMc',
        //     []
        // );
        // print_r($in['invoice_pdf']);

        return redirect()->route('recetario.show');
//        return view('home');
    }

    public function recetario(Request $request, $info = null)
    {
        $current_page = $request->page ? $request->page : 1;
        if (session('calendario_id')) {
            $calendario_list = Calendar::where('id', session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
        } else {
            $calendario_list = Calendar::where('user_id', Auth::user()->id)->first();
        }
        $reset = @$request->reset;

        $request = $request->all();

        unset($request['_token']);
        if (isset($reset)) {
            session()->forget('bookmark');
        }
        $check_bookmark_session = @session('bookmark');
        if (isset($request['filter'])) {
            $request = $request;
        } elseif ($check_bookmark_session) {
            $request = session('bookmark');
        }
        if (empty($request)) {
            session()->forget('bookmark');
            $request = '';
        }

        $tags = Tag::all();
        $ingredientes = Ingrediente::all();
        $calendarios = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
        $nutrientTypes = NutrientType::with('nutrientes')->orderBy('id', 'DESC')->get();
        $busqueda = '';
        $filtrados = array();
        // If there's nothing to filter
        if (isset($request['filter']) || $check_bookmark_session) {
            // Get original query with all options (Edit if more filters are added)
            $bookmark = json_decode('{"tags":[],"num_ingredientes":{"min":"0","max":"10"},"num_tiempo":{"min":"0","max":"60"},"calorias":{"min":"0","max":"900"},"nutrientes":{"1005":{"min":"0","max":"130"},"1079":{"min":"0","max":"30"},"2000":{"min":"0","max":"25"},"1003":{"min":"0","max":"46"},"1004":{"min":"0","max":"60"},"1258":{"min":"0","max":"22"},"1292":{"min":"0","max":"44"},"1293":{"min":"0","max":"22"},"1253":{"min":"0","max":"300"},"1087":{"min":"0","max":"1000"},"1089":{"min":"0","max":"18"},"1090":{"min":"0","max":"320"},"1091":{"min":"0","max":"700"},"1092":{"min":"0","max":"4700"},"1093":{"min":"0","max":"1500"},"1095":{"min":"0","max":"8"},"1103":{"min":"0","max":"55"},"1104":{"min":"0","max":"3000"},"1165":{"min":"0","max":"1"},"1166":{"min":"0","max":"1"},"1167":{"min":"0","max":"14"},"1175":{"min":"0","max":"1"},"1178":{"min":"0","max":"2"},"1162":{"min":"0","max":"75"},"1110":{"min":"0","max":"15"},"1109":{"min":"0","max":"15"},"1185":{"min":"0","max":"90"},"1183":{"min":"0","max":"90"},"1177":{"min":"0","max":"400"},"1180":{"min":"0","max":"425"}}}', true);
            $query = Receta::query();
            $defaults = array();
            if (isset($request['tags'])) {
                $bookmark['tags'] = $request['tags'];
                $defaults['tags'] = $request['tags'];
                foreach ($request['tags'] as $tagSearch) {
                    $query->whereHas('tags', function (Builder $query) use ($tagSearch) {
                        $query->where('tag_id', $tagSearch);
                    });
                }

            }
            if (isset($request['ingrediente_incluir'])) {
                $bookmark['ingrediente_incluir'] = $request['ingrediente_incluir'];
                $defaults['ingrediente_incluir'] = $request['ingrediente_incluir'];
                $query->whereHas('recetaInstruccionReceta.instruccion.ingrediente', function (Builder $query) use ($defaults) {
                    $query->whereIn('ingrediente_id', $defaults['ingrediente_incluir']);
                });
            }
            if (isset($request['ingrediente_excluir'])) {
                $bookmark['ingrediente_excluir'] = $request['ingrediente_excluir'];

                $defaults['ingrediente_excluir'] = $request['ingrediente_excluir'];
                $query->whereDoesntHave('recetaInstruccionReceta.instruccion.ingrediente', function (Builder $query) use ($defaults) {
                    $query->whereIn('ingrediente_id', $defaults['ingrediente_excluir']);
                });
            }
            if (isset($request['num_ingredientes'])) {
                $bookmark['num_ingredientes'] = $request['num_ingredientes'];

                $defaults['num_ingredientes'] = $request['num_ingredientes'];
                if ($defaults['num_ingredientes']['min'] > 0) {
                    $query->has('recetaInstruccionReceta', '>=', $defaults['num_ingredientes']['min']);
                }
                if ($defaults['num_ingredientes']['max'] < 10) {
                    $query->has('recetaInstruccionReceta', '<=', $defaults['num_ingredientes']['max']);
                }
            }
            if (isset($request['num_tiempo'])) {
                $bookmark['num_tiempo'] = $request['num_tiempo'];

                $defaults['num_tiempo'] = $request['num_tiempo'];
                if ($defaults['num_tiempo']['min'] > 0) {
                    $query->where('tiempo', '>=', $defaults['num_tiempo']['min']);
                }
                if ($defaults['num_tiempo']['max'] < 60) {
                    $query->where('tiempo', '<=', $defaults['num_tiempo']['max']);
                }
            }
            if (isset($request['calorias'])) {
                $bookmark['calorias'] = $request['calorias'];

                $defaults['calorias'] = $request['calorias'];

                if ($defaults['calorias']['min'] != 0) {
                    $filtrados[] = 1008;
                    $query->where("nutrient_info->1008->cantidad", '>=', (int) $defaults['calorias']['min']);
                }
                if ($defaults['calorias']['max'] != 900) {
                    $filtrados[] = 1008;
                    $query->where("nutrient_info->1008->cantidad", '<=', (int) $defaults['calorias']['max']);
                }
            }
            if (isset($request['nutrientes'])) {
                $bookmark['nutrientes'] = $request['nutrientes'];
                $defaults['nutrientes'] = $request['nutrientes'];
                foreach ($defaults['nutrientes'] as $clave => $nutriente) {
                    $nutrienteDB = Nutriente::where('fdc_id', $clave)->get()->first();
                    if ($clave != 0) {
                        if ($nutriente['min'] > 0) {
                            $filtrados[] = $clave;
                            if ($nutrienteDB->factor != 0) {
                                $query->where('nutrient_info->' . $clave . '->cantidad', '>', (int) ($nutriente['min'] / $nutrienteDB->factor));
                            } else {
                                $query->where('nutrient_info->' . $clave . '->cantidad', '>', (int) $nutriente['min']);
                            }
                        }
                        if (floor($nutriente['max']) < floor($nutrienteDB->cien_porciento)) {
                            $filtrados[] = $clave;
                            if ($nutrienteDB->factor != 0) {
                                $query->where('nutrient_info->' . $clave . '->cantidad', '<=', (int) ($nutriente['max'] / $nutrienteDB->factor));
                            } else {
                                $query->where('nutrient_info->' . $clave . '->cantidad', '<=', (int) $nutriente['max']);
                            }
                        }
                    }
                }
            }
            session()->forget('bookmark');
            session(['bookmark' => $bookmark]);
            $query->orderBy('recetas.id', 'desc');
            // $perPage = isset($request['ingrediente_incluir']) || isset($request['ingrediente_excluir']) ? 27 : 27;
            // if (auth()->user()->hasRole('free')) {
            //     $recetas = $query->orderBy('free', 'desc')->orderBy('id', 'desc')->get();
            // } else {
                $recetas = $query->get();
            // }

            if (isset($request['ingrediente_excluir'])) {
                foreach ($recetas as $key => $receta) {
                    //fetch the child recipes and forget them
                    $matchingChildrenForExclude = RecetaInstruccionReceta::where('receta_id', $receta->id)->whereNotNull('subreceta_id')->get(['subreceta_id'])->toArray();
                    $childIds = array_map(function ($id) {
                        return $id['subreceta_id'];
                    }, $matchingChildrenForExclude);

                    $children = Receta::whereIn('id', $childIds)->get();
                    foreach ($children as $child) {
                        $r = array_map(function ($ingredients) {
                            if (array_key_exists('ingrediente_id', $ingredients)) {
                                return $ingredients['ingrediente_id'];
                            }
                        }, $child->getIngredientesIds());
                        if (count(array_intersect($r, $request['ingrediente_excluir'])) > 0) {
                            $recetas->forget($key);
                        }
                    }
                }
                $total = $recetas->count();
            }
            if (isset($request['ingrediente_incluir'])) {
                //Include if ALL
                $matchingRecipeIds = [];
                foreach ($recetas as $key => $receta) {
                    $r = array_map(function ($ingredients) {
                        if (array_key_exists('ingrediente_id', $ingredients)) {
                            return $ingredients['ingrediente_id'];
                        }
                    }, $receta->getIngredientesIds());
                    if (count(array_intersect($r, $request['ingrediente_incluir'])) != count($request['ingrediente_incluir'])) {
                        //forget this recipe
                        //check if this recipe alongwith the Parent recipe can fulfil the Include All condition
                        //if yes - include the Parent here, and ignore the subrecipe
                        $recetas->forget($key);
                        $parentMatching = $this->checkIfCombinedWithParentsIncludeAll($receta, $request['ingrediente_incluir'], array_intersect($r, $request['ingrediente_incluir']));
                        if ($parentMatching) {
                            $recetas->add($parentMatching);
                        }
                    } else {
                        //if the recipe has it all, then get its immediate parent in the results as well
                        $matchingParentsForInclude = RecetaInstruccionReceta::where('subreceta_id', $receta->id)->get(['receta_id'])->toArray();
                        $parentIds = array_map(function ($id) {
                            return $id['receta_id'];
                        }, $matchingParentsForInclude);
                        if (isset($request['tags']) && in_array("18", $request['tags']) || isset($request['tags']) && in_array("25", $request['tags'])) {
                            $parents = Receta::whereIn('id', $parentIds)->whereHas('tags', function ($query) {
                                $query->whereIn('tag_id', [18, 25]);
                            })->get();
                        } else {
                            $parents = Receta::findMany($parentIds);
                        }
                        foreach ($parents as $parent) {
                            $recetas->add($parent);
                        }
                    }
                }
                $total = $recetas->count();
            }

            $bookmarks = Bookmark::whereUserId(Auth::user()->id)->get();
            $filter = [
                'filter' => @$request['filter'],
                'nutrientes' => @$request['nutrientes'],
                'num_tiempo' => @$request['num_tiempo'],
                'calorias' => @$request['calorias'],
                'num_ingredientes' => @$request['num_ingredientes'],
                'ingrediente_excluir' => @$request['ingrediente_excluir'],
                'ingrediente_incluir' => @$request['ingrediente_incluir'],
                'tags' => @$request['tags'],
            ];

            $recetas_filter = $this->paginate($recetas, '27', $current_page, $options = []);
            //dd($recetas_filter);
            $total = $recetas_filter->total();
            $recetas = $recetas_filter;
            if ($info) {
                return view('recetario', compact('calendarios', 'filter', 'calendario_list', 'bookmarks', 'recetas', 'total', 'ingredientes', 'tags', 'defaults', 'nutrientTypes', 'busqueda', 'filtrados'))->with($info);
            } else {
                return view('recetario', compact('calendarios', 'filter', 'calendario_list', 'bookmarks', 'recetas', 'total', 'ingredientes', 'tags', 'defaults', 'nutrientTypes', 'busqueda', 'filtrados'));
            }
        } else {
            session()->forget('bookmark');

            // if (auth()->user()->hasRole('free')) {
            //     $recetas = Receta::orderBy('free', 'desc')->orderBy('id', 'desc')->paginate(27);
            // } else {
                $recetas = Receta::orderBy('id', 'desc')->paginate(27);
            // }
            $bookmarks = Bookmark::whereUserId(Auth::user()->id)->get();
            $total = $recetas->total();
            if ($info) {
                return view('recetario', compact('calendarios', 'calendario_list', 'bookmarks', 'recetas', 'total', 'ingredientes', 'tags', 'nutrientTypes', 'filtrados'))->with($info);
            } else {
                return view('recetario', compact('calendarios', 'calendario_list', 'bookmarks', 'recetas', 'total', 'ingredientes', 'tags', 'nutrientTypes', 'filtrados'));
            }

        }
    }
    public function paginate($items, $perPage = 27, $page, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);

        $items = $items instanceof Collection ? $items : Collection::make($items);

        $paginator = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);

        $url = '/recetario';
        $paginator->setPath($url);
        return $paginator;
    }

    public function checkIfCombinedWithParentsIncludeAll($receta, $includeIngreds, $mergeIngreds)
    {
        $parents = RecetaInstruccionReceta::where('subreceta_id', $receta->id)->get(['receta_id'])->toArray();
        if (count($parents) == 0) {
            return false;
        }
        $result = null;
        foreach ($parents as $parent) {
            $parentReturn = Receta::find($parent['receta_id']);
            if ($parentReturn) {
                $r = array_map(function ($ingredients) {
                    if (array_key_exists('ingrediente_id', $ingredients)) {
                        return $ingredients['ingrediente_id'];
                    }
                }, $parentReturn->getIngredientesIds());
                if (count(array_unique(array_intersect(array_merge($r, $mergeIngreds), $includeIngreds))) == count($includeIngreds)) {
                    $result = $parentReturn;
                    break;
                }
            }
        }
        return $result;
        // die();
        // if ($receta->id == 79 && count(array_unique(array_intersect(array_merge($r, $mergeIngreds), $includeIngreds))) == count($includeIngreds)) {
        //     echo 'Sub->';
        //     echo $receta->id;
        //     print_r($receta->titulo);
        //     echo 'Its parent->';
        //     echo $parent->titulo;
        //     echo $parentId[0]['receta_id'];

        //     print_r($r);
        //     print_r($mergeIngreds);
        //     print_r($includeIngreds);
        //     print_r(array_merge($r, $mergeIngreds));
        //     print_r(array_unique(array_intersect(array_merge($r, $mergeIngreds), $includeIngreds)));

        // }
    }
    public function getUrl($recipe_id)
    {
        return route('recipe.show', [Receta::find($recipe_id)->slug]);
    }

    public function saveBookmark(Request $request)
    {
        Bookmark::create(['user_id' => Auth::user()->id, 'filters' => json_encode(session('bookmark')), 'name' => $request->name]);

        $request = new Request();
        $request->setMethod('POST');
        if (session('bookmark')) {
            $request->merge(session('bookmark'));
        } else {
            $request->merge([]);
        }
        return $this->recetario($request);
    }

    public function getBookmark(Request $request)
    {
        if ($request->bookmarks) {
            if ($request->delete == 0) {
                $filters = Bookmark::whereIn('id', $request->bookmarks)->get();
                $arrays_filter = [];
                foreach ($filters as $filter) {
                    $content = json_decode($filter->filters, true);
                    $arrays_filter[] = $content;
                }
                $last_array = [];
                $i = 0;
                foreach ($arrays_filter as $key => $element) {
                    if (array_key_last($arrays_filter) == $key) {
                        $last_array = array_unique(array_merge($element, $arrays_filter[$key]), SORT_REGULAR);
                    } else {
                        $last_array = array_unique(array_merge($element, $arrays_filter[$key + $i]), SORT_REGULAR);
                    }
                }

                $request = new Request();
                $request->setMethod('POST');
                $request->merge($last_array);
                $request['filter'] = true;
                return $this->recetario($request);
            } else {
                $count = count($request->bookmarks);
                Bookmark::destroy($request->bookmarks);

                $request = new Request();
                $request->setMethod('POST');
                if (session('bookmark')) {
                    $request->merge(session('bookmark'));
                } else {
                    $request->merge([]);
                }
                return $this->recetario($request, ['info' => 'Se han eliminado ' . $count . ' marcadores']);
            }
        } else {
            $request = new Request();
            $request->setMethod('POST');
            if (session('bookmark')) {
                $request->merge(session('bookmark'));
            } else {
                $request->merge([]);
            }

            return $this->recetario($request, ['info' => 'No seleccionaste ningún marcador, marca uno o varios e intenta de nuevo']);
        }
    }

    public function calendarioLista(Request $request)
    {
        if ($request->user()->hasPermission('lista_view')) {
            $calendarios = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();

            if ($request->session()->has('calendario_id')) {
                $check_calender = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->exists();
                if ($check_calender) {
                    $calendario = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
                } else {
                    $calendario = Calendar::where('user_id', Auth::user()->id)->first();
                    if ($calendario) {
                        session(['calendario_id' => $calendario->id]);
                    }
                }
                ///$calendario = Calendar::where('id',$request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendario = Calendar::where('user_id', Auth::user()->id)->first();
            }
            if ($calendario) {

                $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendario->id])->get();
                $taken_ingredientes = json_encode($taken_ingredientes);
                $categorias = Categoria::orderBy('sort', 'ASC')->get();
                $lista_ingredientes = ListaIngredientes::where('calendario_id', $calendario->id)->get();
                return view('calendario-lista', compact('calendarios', 'categorias', 'calendario', 'taken_ingredientes', 'lista_ingredientes'));
            }
            return view('calendario-lista', compact('calendario'));

        }
        return abort(404);
    }
    // Get all lista ingredients ListaRenderAll

    public function ListaRenderAll(Request $request)
    {
        if ($request->ajax()) {
            if ($request->session()->has('calendario_id')) {
                $calendario = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendario = Calendar::where('user_id', Auth::user()->id)->first();
            }
            $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendario->id])->get();
            $taken_ingredientes = json_encode($taken_ingredientes);
            $categorias = Categoria::orderBy('sort', 'ASC')->get();
            $ingredients = [];
            $new_lista_count = 0;
            foreach ($categorias as $key => $category) {
                $category_id = $category->id;
                // $lista_ingredientes = ListaIngredientes::where('categoria', $category_id)->where('calendario_id', $calendario->id)->get();
                // $new_lista_count += count($lista_ingredientes);
                $ingredients[$category_id] = getRelatedIngrediente($calendario->id, $category_id, 'list');
                $new_lista_count += count($ingredients[$category_id]);
            }

            //$lista_html = view('/partials/sub_templates/lista_ingrediente', compact('category_id', 'categorias', 'calendario', 'taken_ingredientes', 'lista_ingredientes'))->render();
            return response()->json(['success' => true, 'ingredients' => $ingredients, 'new_lista_count' => $new_lista_count]);
        }
        return abort(404);
    }

    public function ListaRender(Request $request, $id)
    {
        if ($request->ajax()) {
            if ($request->session()->has('calendario_id')) {
                $calendario = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendario = Calendar::where('user_id', Auth::user()->id)->first();
            }
            $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendario->id])->get();
            $taken_ingredientes = json_encode($taken_ingredientes);
            $categorias = Categoria::orderBy('sort', 'ASC')->get();
            $category_id = $id;
            $lista_ingredientes = ListaIngredientes::where('categoria', $category_id)->where('calendario_id', $calendario->id)->get();
            $new_lista_count = count($lista_ingredientes);
            $ingredients = getRelatedIngrediente($calendario->id, $category_id, 'list');
            // dd($ingredients);
            $modal_html = '';
            if (auth()->user()->hasRole('professional')) {
                $modal_html = view('/partials/modals/lista_modal_info', compact('category_id', 'ingredients', 'calendario', 'taken_ingredientes', 'lista_ingredientes'))->render();
            }

            $cLabels = json_decode($calendario->labels, true);
            $ingre_data_sort = [];
            foreach ($cLabels['days'] as $daykey => $value) {
                foreach ($ingredients as $key1 => $value1) {
                    if ($value1['day'] == $daykey) {
                        $ingre_data_sort[$daykey][] = $value1;
                        if (isset($value1['repeat'])) {
                            foreach ($value1['repeat'] as $key2 => $repeat) {
                                if ($daykey == $repeat['day']) {
                                    $ingre_data_sort[$daykey][] = $repeat;
                                }
                                # code...
                            }
                        }
                    }
                    # code...
                }
            }
            // echo"<pre>";
            // print_r($ingre_data_sort);

            $lista_html = view('/partials/sub_templates/lista_ingrediente', compact('category_id', 'categorias', 'calendario', 'taken_ingredientes', 'lista_ingredientes'))->render();
            return response()->json(['success' => true, 'modal_html' => $modal_html, 'ingredients' => $ingredients, 'lista_html' => $lista_html, 'new_lista_count' => $new_lista_count]);
        }
        return abort(404);
    }

    public function calendarioListaPdf(Request $request)
    {
        if ($request->ajax()) {
            $data = [];
            $lista_recipient_email_address = auth()->user()->email;
            if ($request->has('lista_recipient_email_address')) {
                if (!empty($request->input('lista_recipient_email_address'))) {
                    $lista_recipient_email_address = $request->lista_recipient_email_address;
                }
            }
            if ($request->session()->has('calendario_id')) {
                $calendario = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
            } else {
                $calendario = Calendar::where('user_id', Auth::user()->id)->first();
            }
            $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendario->id])->get();
            $categorias = Categoria::orderBy('sort', 'ASC')->get();
            $lista_ingredientes = ListaIngredientes::where('calendario_id', $calendario->id)->get();
            $recipe_ingredients = json_decode($request->lista_ingredients);
            $ingredients_count = (count((array) $recipe_ingredients) + $lista_ingredientes->count()) - count($taken_ingredientes);

            if (auth()->user()->role_id == 3) {
                

                if (auth()->user()->theme == 1) {
                    $pdf = PDF::loadView('pdf.classic.classic-lista', array('ingredients_count' => $ingredients_count, 'recipe_ingredients' => $recipe_ingredients, 'calendario' => $calendario, 'categorias' => $categorias, 'taken_ingredientes' => $taken_ingredientes, 'lista_ingredientes' => $lista_ingredientes));
                } elseif (auth()->user()->theme == 2) {
                    $pdf = PDF::loadView('pdf.modern.modern-lista', array('ingredients_count' => $ingredients_count, 'recipe_ingredients' => $recipe_ingredients, 'calendario' => $calendario, 'categorias' => $categorias, 'taken_ingredientes' => $taken_ingredientes, 'lista_ingredientes' => $lista_ingredientes));
                } else {
                    $pdf = PDF::loadView('pdf.bold.bold-lista', array('ingredients_count' => $ingredients_count, 'recipe_ingredients' => $recipe_ingredients, 'calendario' => $calendario, 'categorias' => $categorias, 'taken_ingredientes' => $taken_ingredientes, 'lista_ingredientes' => $lista_ingredientes));
                }
            } else {
                $pdf = PDF::loadView('pdf.lista-pdf', array('ingredients_count' => $ingredients_count, 'recipe_ingredients' => $recipe_ingredients, 'calendario' => $calendario, 'categorias' => $categorias, 'taken_ingredientes' => $taken_ingredientes, 'lista_ingredientes' => $lista_ingredientes));
            }
            if ($request->has('lista_recipient_email_address')) {
                if (filter_var($lista_recipient_email_address, FILTER_VALIDATE_EMAIL)) {
                    $data = array(
                        'email' => $lista_recipient_email_address,
                        'title' => "¡Tu lista esta lista!",
                        'filename' => $calendario->title,
                        'current_time' => todaySpanishDay()
                    );
                    if (!empty($request->input('plantillas'))) {
                        $data['plantillas'] = utf8_decode(urldecode(request()->plantillas));
                    } else {
                        $data['plantillas'] = '';
                    }
                    $mail_procedure = Mail::send('email.send-lista-mail', $data, function ($message) use ($data, $pdf) {
                        $message->to($data["email"], $data["email"])
                            ->subject($data["title"])
                            ->attachData($pdf->output(), $data['filename'] . ".pdf");
                    });
                    Mail::send('email.delivery-email', ['type' => 'Lista','meal_type'=>'Tu lista', 'to' => $data["email"],
                    'title'=>$data['title'],'current_time' => todaySpanishDay()], 
                        function ($message) use ($data, $pdf) {
                        $message->to(auth()->user()->bemail, auth()->user()->bemail)
                            ->subject('Tu lista de compras fue entregada')
                            ->attachData($pdf->output(), $data['filename'] . ".pdf");
                    });
                    $json_data['message'] = "Se envio por mail exitosamente";
                    $json_data['status'] = "success";
                } else {
                    $json_data['message'] = "El correo electrónico del destinatario no es válido";
                    $json_data['status'] = "error";
                }
            } else {
                // return $pdf->stream('Receta.pdf');
                // die();
                return $pdf->download($calendario->title . '.pdf');
            }
            echo json_encode($json_data);
            die();
        } else {
            dd("not allowed");
        }
    }

    public function calendarioUpdateLista(Request $request)
    {
        if ($request->user()->hasPermission('lista_view')) {
            if ($request->calendario_id && $request->ajax()) {

                $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $request->calendario_id, 'categoria_id' => $request->ingred_cat, 'ingrediente_id' => $request->ingred_id, 'ingrediente_type' => $request->ingred_type]);
                if ($taken_ingredientes->first()) {
                    $taken_ingredientes->delete();
                    $data = 'delete';
                } else {
                    DB::table('lista_ingrediente_taken')->insert(['calendario_id' => $request->calendario_id, 'categoria_id' => $request->ingred_cat, 'ingrediente_id' => $request->ingred_id, 'ingrediente_type' => $request->ingred_type]);
                    $data = 'create';
                }
                $taken_ingredientes_lista = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $request->calendario_id])->get();
                $taken_ingredientes_lista = json_encode($taken_ingredientes_lista);
                return response()->json(['success' => true, 'data' => $taken_ingredientes_lista]);
            }
        }
        return abort(404);
    }
    public function ListaEmail(Request $request)
    {
        if ($request->session()->has('calendario_id')) {
            $calendario = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
        } else {
            $calendario = Calendar::where('user_id', Auth::user()->id)->first();
        }
        $taken_ingredientes = DB::table('lista_ingrediente_taken')->where(['calendario_id' => $calendario->id])->get();
        $categorias = Categoria::orderBy('sort', 'ASC')->get();
        $lista_ingredientes = ListaIngredientes::where('calendario_id', $calendario->id)->get();
        $recipe_ingredients = json_decode($request->lista_ingredients);
        $data = [
            'subject' => '¡Tu lista de "' . $calendario->title . '" está lista!',
            'email' => $request->user()->email,
            'categorias' => $categorias,
            'calendario' => $calendario,
            'recipe_ingredients' => $recipe_ingredients,
            'lista_ingredientes' => $lista_ingredientes,
            'taken_ingredientes' => $taken_ingredientes,
        ];
        //return view('email.lista-email', compact('categorias','calendario','taken_ingredientes','lista_ingredientes'));

        Mail::send('email.lista-email', $data, function ($message) use ($data) {
            $message->to($data['email'])
                ->subject($data['subject']);
        });
        return response()->json(['success' => true, 'info' => 'Enviar correo electrónico']);
    }
    public function listaIngredientes(Request $request)
    {
        if ($request->user()->hasPermission('lista_view')) {
            $request->validate([
                'cantidad' => "required|numeric",
                'nombre' => ['required', 'string'],
                'categoria' => ['required', 'string'],
            ]);
            $ListaIngredientes = ListaIngredientes::create($request->all());
            return redirect()->route('calender.listas')->with(['info' => 'Lista actualizada']);
        }
        return abort(404);

    }
    public function UpdatelistasIngredients(Request $request, $id)
    {

        if ($request->user()->hasPermission('lista_view')) {

            $request->validate([
                'cantidad' => "required|numeric",
                'nombre' => ['required', 'string'],
                'categoria' => ['required', 'string'],
            ]);
            unset($request['_token']);
            $ListaIngredientes = ListaIngredientes::where('id', $id)->update($request->all());
            return redirect()->route('calender.listas')->with(['info' => 'Lista actualizada']);
        }
        return abort(404);

    }
    public function deletelistasIngredients($id)
    {
        ListaIngredientes::where('id', $id)->delete();
        return response()->json(['success' => true, 'info' => 'Lista actualizada']);
    }
    public function pruebaNutrimental()
    {
        Receta::first()->pruebaNutrimental();
    }

    public function recetasAlgolia()
    {
        foreach (Receta::all() as $receta) {
            $receta->save();
        }
        return 0;
    }

    public function planes()
    {
        if (auth()->user()->hasRole('free')) {
            $planes = Plan::where('id', '20')->get();
        } else {
            $planes = Plan::whereNull('deleted_at')->whereIn('tipo_id',[4,Auth::user()->role_id])->get();
        }
        if (session('calendario_id')) {
            $calendario_list = Calendar::where('id', session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
        } else {
            $calendario_list = Calendar::where('user_id', Auth::user()->id)->first();
        }
        $calendarios = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
        return view('planes', compact('planes', 'calendario_list', 'calendarios'));
    }
    public function planesCalendario(Request $request, $id)
    {
        $planes = Plan::where('id', $id)->whereIn('tipo_id',[4,Auth::user()->role_id])->first();
        if(!empty($planes))
        {
            $calendar = $planes->plan_receta;
            $calendarios = Calendar::where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
            return view('planes-calendario', compact('calendar', 'calendarios', 'planes'));
        }
        else
        {
            return abort(404);
        }
        
    }
    public function copyPlanes(Request $request, $id)
    {
        $planes = Plan::where('id', $id)->whereIn('tipo_id',[4,Auth::user()->role_id])->first();
        if(!empty($planes))
        {
            $calendar = $planes->plan_receta;
            $servingsCalculated = $this->manipulateServings($calendar);
            $mainRecipes = (array)json_decode($calendar->main_schedule);
            $sideRecipes = (array) json_decode($calendar->sides_schedule);
            foreach (json_decode($calendar->main_servings) as $daykey => $value) {
                foreach ($value as $mealkey => $value1) {
                    $dayMeals = (array)$mainRecipes[$daykey];
                    $sideAdd = 0;
                    if(isset($servingsCalculated[1][$dayMeals[$mealkey]])){
                        $sideAdd = $servingsCalculated[1][$dayMeals[$mealkey]];
                    }
                    if($dayMeals[$mealkey])
                        $mServings[$daykey][$mealkey] = ($servingsCalculated[0][$dayMeals[$mealkey]]+$sideAdd) * $request->calendar_scale * $value1;// $value1 != null ? ($value1 * $request->calendar_scale) / 2 : $value1;
                    else
                        $mServings[$daykey][$mealkey]=0;
                }
            }
            foreach (json_decode($calendar->sides_servings) as $daykey => $value) {
                foreach ($value as $mealkey => $value1) {
                    $dayMeals = (array)$sideRecipes[$daykey];
                    $mainAdd = 0;
                    if (isset($servingsCalculated[0][$dayMeals[$mealkey]])) {
                        $mainAdd = $servingsCalculated[0][$dayMeals[$mealkey]];
                    }
                    if($dayMeals[$mealkey])
                        $sServings[$daykey][$mealkey] = ($servingsCalculated[1][$dayMeals[$mealkey]]+$mainAdd) * $request->calendar_scale * $value1;
                    else
                        $sServings[$daykey][$mealkey] = 0;//$value1 != null ? ($value1 * $request->calendar_scale) / 2 : $value1;
                }
            }
            $NewCalendar = Calendar::create([
                'user_id' => Auth::user()->id,
                'title' => $request->calendar_title,
                'main_schedule' => $calendar->main_schedule,
                'main_leftovers' => $calendar->main_leftovers,
                'main_servings' => json_encode($mServings),
                'main_racion' => json_encode(config()->get('constants.main_racion')),
                'sides_schedule' => $calendar->sides_schedule,
                'sides_leftovers' => $calendar->sides_leftovers,
                'sides_servings' => json_encode($sServings),
                'sides_racion' => json_encode(config()->get('constants.sides_racion')),
                'labels' => $calendar->labels,
            ]);
            session(['calendario_id' => $NewCalendar->id]);
            if ($request->ajax()) {
                return response()->json(['success' => true, 'data' => $NewCalendar]);
            }

            return redirect()->route('calendar.view')->with(['info' => 'Calendario copiado']);
        } 
        else
        {
            return abort(404);
        }
        
    }

    public function manipulateServings($calendar){
        $mainMeals =(array) json_decode($calendar->main_schedule);
        $sideMeals = (array)json_decode($calendar->sides_schedule);
        $mainLeftovers =(array) json_decode($calendar->main_leftovers);
        $sideLeftovers = (array) json_decode($calendar->sides_leftovers);
        $mergedMains = array_merge_recursive($mainMeals, $mainLeftovers);
        $mealsOnDaysMapping = [];
        foreach($mergedMains as $day=>$meals ){
            foreach($meals as $meal=>$obj){
                $mealsOnDaysMapping[$obj[0]][$day]=$obj[1]?'Leftover':'No';
            }
        }
        $daysOfWeek = ['day_1','day_2','day_3','day_4','day_5','day_6','day_7'];
        $mMealsOnDays = [];
        foreach($mealsOnDaysMapping as $recipeId=>$days){
            foreach($daysOfWeek as $day){
                $mMealsOnDays[$recipeId][] = isset($mealsOnDaysMapping[$recipeId][$day])?$mealsOnDaysMapping[$recipeId][$day]:-1;
            }
        }
        //calculate servings for mains here
        $mServings = [];
        foreach($mMealsOnDays as $recipeId=>$leftovers){
            $counts = array_count_values($leftovers);
            if($recipeId){
                if(isset($counts['Leftover']) && $counts['Leftover']>0)
                    $mServings[$recipeId] = (isset($counts['No'])?$counts['No']:0) + (isset($counts['Leftover'])?$counts['Leftover']:0);
                else
                    $mServings[$recipeId] = 1;
                    
            }
        }

        //Now sides
        $mergedSides = array_merge_recursive($sideMeals, $sideLeftovers);
        $mealsOnDaysMapping = [];
        foreach($mergedSides as $day=>$meals ){
            foreach($meals as $meal=>$obj){
                $mealsOnDaysMapping[$obj[0]][$day]=$obj[1]?'Leftover':'No';
            }
        }
        $daysOfWeek = ['day_1','day_2','day_3','day_4','day_5','day_6','day_7'];
        $sMealsOnDays = [];
        foreach($mealsOnDaysMapping as $recipeId=>$days){
            foreach($daysOfWeek as $day){
                $sMealsOnDays[$recipeId][] = isset($mealsOnDaysMapping[$recipeId][$day])?$mealsOnDaysMapping[$recipeId][$day]:-1;
            }
        }
        $sServings = [];
        foreach($sMealsOnDays as $recipeId=>$leftovers){
            $counts = array_count_values($leftovers);
            if($recipeId){
                if(isset($counts['Leftover']) && $counts['Leftover']>0)
                    $sServings[$recipeId] = (isset($counts['No'])?$counts['No']:0) + (isset($counts['Leftover'])?$counts['Leftover']:0);
                else
                    $sServings[$recipeId] =1;
            }
        }
        return [$mServings,$sServings];
        //return [294=>[-1,2,0,0,-1,-1,-1]];
    }

    public function planesPdf(Request $request)
    {
        $planes = Plan::where('id', $request->plan_id)->first();
        $calendar = $planes->plan_receta;
        $calendar->title = $planes->nombre;
        $pdf = PDF::loadView('pdf.calendario-pdf', array('calendar' => $calendar))->setPaper('a4', 'landscape');
        return $pdf->download($planes->nombre . '.pdf');
    }

    public function misPlanes()
    {
        return view('mis-planes');
    }

    public function miRecetario()
    {
        $tags = Tag::all();
        $ingredientes = Ingrediente::all();

        // dd($ingredientes);

        return view('mi-recetario', compact('tags', 'ingredientes'));
    }

    public function receta()
    {
        return view('receta');
    }
    public function testNutriente($slug)
    {
        $receta = Receta::where('slug', $slug)->firstOrFail();
        $nutrientes = $receta->getInformacionNutrimental();
        return $nutrientes;
    }
    public function saveJson()
    {
        $recetas = Receta::all();
        foreach ($recetas as $receta) {
            $info = 0;
            $info = @$receta->getInformacionNutrimental();
            if (!$info) {
                dump('fallo - ' . $receta->title);
            }
        }
        return $recetas;
    }

    public function receta_vista(Request $request, $slug)
    {
        ini_set('xdebug.var_display_max_depth', '-1');
        ini_set('xdebug.var_display_max_children', '-1');
        ini_set('xdebug.var_display_max_data', '-1');
        $receta = Receta::where('slug', $slug)->firstOrFail();
        $calendarios = Calendar::where('user_id', Auth::user()->id)->get();
        $tips = $receta->getTips();
        $nutrientes = $receta->getInformacionNutrimental();
        if ($request->session()->has('calendario_id')) {
            $calendario = Calendar::where('id', $request->session()->get('calendario_id'))->where('user_id', Auth::user()->id)->first();
        } else {
            $calendario = Calendar::where('user_id', Auth::user()->id)->first();
        }
        usort($nutrientes['info'], function ($item1, $item2) {
            if (!$item1['orden']) {
                return 1;
            }

            if (!$item2['orden']) {
                return -1;
            }

            return $item1['orden'] <=> $item2['orden'];
        });

        $notas_tiempo = $receta->getNotasTiempo();

        $equivalences = Equivalence::first();
        $serv = @$request->ser;
        return view('receta', compact('calendarios', 'equivalences', 'serv', 'calendario', 'receta', 'tips', 'notas_tiempo', 'nutrientes'));
    }

    public function getNutrientesIngredientes($id)
    {
        return Receta::find($id) ? Receta::find($id)->getNutrientesIngredientes() : [];
    }

    // public function ingredienteMedida($ing){
    //   $ingrediente = Ingrediente::find($ing);

    //   if($ingrediente){
    //     return $ingrediente->medida ? $ingrediente->medida : null;
    //   }
    //   else{
    //     return null;
    //   }
    // }

    public function reaction($recipe_id, $reaction)
    {
        $existing_reaction = Reaction::whereUserId(Auth::user()->id)->whereRecipeId($recipe_id)->first();
        if ($existing_reaction) {
            $existing_reaction->is_like = $reaction;
            $existing_reaction->save();
        } else {
            Reaction::create([
                'is_like' => $reaction,
                'user_id' => Auth::user()->id,
                'recipe_id' => $recipe_id,
            ]);
        }

        return 1;
    }

    public function comment(Request $request)
    {
        $user = Auth::user();
        $comment_from_request = $request->comment;
        $recipe_id = $request->receta_id;
        $receta = Receta::find($request->receta_id);

        if ($request->responding_comment) {

            $comment = Comment::find($request->responding_comment);
            $comment->answered = 1;
            $comment->save();

            if ($comment->user->preference->mentions) {
                $comment->notify(new CommentAnsweredNotification($receta));
            }
        }

        if ($user->id == 2 || $user->id == 3) {
            $comment = Comment::create([
                'comment' => $comment_from_request,
                'user_id' => $user->id,
                'is_a_response' => substr($comment_from_request, 0, 1) == '@' ? 1 : 0,
                'receta_id' => $receta->id,
                'from_admin' => 1,
            ]);
        } else {
            $comment = Comment::create([
                'comment' => $comment_from_request,
                'user_id' => $user->id,
                'is_a_response' => substr($comment_from_request, 0, 1) == '@' ? 1 : 0,
                'receta_id' => $receta->id,
            ]);
        }
        //when any comment added
        if(isset($comment)):
            if(!empty($comment->id)):
                $user = User::where('id','=',2)->first();
                $user->notify(new CommentAddedNotification($receta));
            endif;
        endif;
        Receta::find($recipe_id)->comments()->syncWithoutDetaching($comment);
        return json_encode(['author' => $comment->user->name, 'comment' => $comment->comment, 'time' => $comment->elapsed_time]);
    }

    public function deleteComment($comment_id)
    {
        Comment::whereId($comment_id)->delete();
        return redirect()->back();
    }

    public function pdf($recipe_id)
    {
        $recipe = Receta::find($recipe_id);
        $nutritionals = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
        if ($nutritionals) {
            $nutritionals_info = json_decode($nutritionals->nutritional_info);
        } else {
            $nutritionals_info = config()->get('constants.nutritients');
        }
        if (auth()->user()->role_id == 3) {
            if (auth()->user()->theme == 1) {
                $pdf = PDF::loadView('pdf.classic.classic-recipe', array('recipe' => $recipe, 'nutritionals_info' => $nutritionals_info))->setPaper('a4', 'portrait');
            } elseif (auth()->user()->theme == 2) {
                $pdf = PDF::loadView('pdf.modern.modern-recipe', array('recipe' => $recipe, 'nutritionals_info' => $nutritionals_info))->setPaper('a4', 'portrait');
            } else {
                $pdf = PDF::loadView('pdf.bold.bold-recipe', array('recipe' => $recipe, 'nutritionals_info' => $nutritionals_info))->setPaper('a4', 'portrait');
            }

        } else {
            $pdf = PDF::loadView('pdf.recipe', array('recipe' => $recipe));
        }

        //return $pdf->stream('Receta.pdf');
        return $pdf->download($recipe->titulo . '.pdf');
        //return view('pdf.recipe-modern', compact('recipe','nutritionals_info'));
    }

    //send email in pdf from receta
    public function sendPdfMail(Request $request, $recipe_id)
    {
        $data = [];
        if (!empty($request->input('recipient_email_address'))) {
            $recipient_custom_email = $request->recipient_email_address;
        } else {
            $recipient_custom_email = auth()->user()->email;
        }
        if ($request->ajax()) {
            if (filter_var($recipient_custom_email, FILTER_VALIDATE_EMAIL)) {

                $mail_blade = 'pdf.recipe';

                $recipe = Receta::find($recipe_id);
                $nutritionals = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
                if ($nutritionals) {
                    $nutritionals_info = json_decode($nutritionals->nutritional_info);
                } else {
                    $nutritionals_info = config()->get('constants.nutritients');
                }
                if (auth()->user()->role_id == 3) {

                    if (auth()->user()->theme == 1) {
                        $pdf = PDF::loadView('pdf.classic.classic-recipe', array('recipe' => $recipe, 'nutritionals_info' => $nutritionals_info))->setPaper('a4', 'portrait');
                        $mail_blade = 'pdf.recipe-classic';
                    } elseif (auth()->user()->theme == 2) {
                        $pdf = PDF::loadView('pdf.modern.modern-recipe', array('recipe' => $recipe, 'nutritionals_info' => $nutritionals_info))->setPaper('a4', 'portrait');
                    } else {
                        $pdf = PDF::loadView('pdf.bold.bold-recipe', array('recipe' => $recipe, 'nutritionals_info' => $nutritionals_info))->setPaper('a4', 'portrait');
                    }
                } else {
                    $pdf = PDF::loadView('pdf.recipe', array('recipe' => $recipe));
                }

                $data = array(
                    'email' => $recipient_custom_email,
                    'title' => "¡Tu receta esta lista!",
                    'recipe' => $recipe,
                    'nutritionals_info' => $nutritionals_info,
                    'current_time' => todaySpanishDay()
                );

                if (!empty($request->input('plantillas'))) {
                    $data['plantillas'] = utf8_decode(urldecode(request()->plantillas));
                } else {
                    $data['plantillas'] = '';
                }

                $mail_procedure = Mail::send('email.send-recipe', $data, function ($message) use ($data, $pdf) {
                    $message->to($data["email"], $data["email"])
                        ->subject($data["title"])
                        ->attachData($pdf->output(), $data['recipe']->titulo . ".pdf");
                });
                Mail::send('email.delivery-email', ['type' => 'Receta', 'meal_type'=>'Receta','to' => $data["email"],
                    'title'=>$data['title'],'current_time' => todaySpanishDay()], 
                    function ($message) use ($data, $pdf) {
                    $message->to(auth()->user()->bemail, auth()->user()->bemail)
                        ->subject('Tu receta fue entregada.')
                        ->attachData($pdf->output(), $data['recipe']->titulo . ".pdf");
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
            dd("not allowed");
        }
    }
    public function getCalendarScheduleJson(Request $request){
         if ($request->ajax()) {
             $data = [];
             $calendarios = Calendar::select('id','main_schedule','sides_schedule')->where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();
            foreach ($calendarios as $key => $value) {
                $data[$value->id] = $value;
            }
            echo json_encode(['data'=>$data]);
            die();
        }
    }
    public function adjustSubrecetas(){
        $resultsWithSubrecetas = Receta::select('id','tips')->where('tips','LIKE','%receta[%')->get();
        foreach ($resultsWithSubrecetas as $idx => $receta) {
            $tips = preg_split('/\n|\r\n?/', $receta['tips']);
            $tipsReturn = array();
            foreach ($tips as $key => $tip) {
                $tipsReturn[] = $tip;
            }
            $newTips = [];
            $update = true;
            for ($i = 0; $i < count($tipsReturn); $i++) {
                if(strpos($tipsReturn[$i], 'receta[') !== false) {
                    $inicial = strpos($tipsReturn[$i], 'receta[');
                    $final = strpos($tipsReturn[$i], ']', $inicial);

                    $recetaString = substr($tipsReturn[$i], $inicial + 7, $final - $inicial - 7);
                    $recetaTip = Receta::where('slug', 'like', '%'.$recetaString.'%')->get();
                    if(count($recetaTip)>0){
                        $tipsReturn[$i] = 'receta['.$recetaTip->first()['id'].']';
                    }else{
                        $update = false;
                        echo $recetaString.' not found';
                        // break;

                    }
                }
                array_push($newTips,$tipsReturn[$i]);
            }
            if($update){
                print_r($newTips);
                $recetaToSave = Receta::where('id',$receta['id'])->first();
                $recetaToSave->tips = implode(PHP_EOL,$newTips);
                $recetaToSave->save();
            }
        }
        dd("Done updating sub recipe links in tips");
    }   
}
