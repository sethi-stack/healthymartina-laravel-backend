<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;

// VALIDATION: change the requests to match your own file names if you need form validation
use App\Http\Requests\CommentRequest as StoreRequest;
use App\Http\Requests\CommentRequest as UpdateRequest;
use App\Models\Comment;
use App\Models\Receta;
use Backpack\CRUD\CrudPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Class CommentCrudController
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class CommentCrudController extends CrudController
{
    public function setup()
    {
        /*
        |--------------------------------------------------------------------------
        | CrudPanel Basic Information
        |--------------------------------------------------------------------------
        */
        $this->crud->setModel('App\Models\Comment');
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/comment');
        $this->crud->setEntityNameStrings('Comentario', 'Comentarios');

        $this->crud->addFilter(
            [ // add a "simple" filter called Draft 
                'type' => 'simple',
                'name' => 'answered',
                'label' => 'No respondidos'
            ],
            false, // the simple filter has no values, just the "Draft" label specified above
            function () { // if the filter is active (the GET parameter "draft" exits)
                $this->crud->addClause('where', 'answered', '0');
                // we've added a clause to the CRUD so that only elements with draft=1 are shown in the table
                // an alternative syntax to this would have been
                // $this->crud->query = $this->crud->query->where('draft', '1'); 
                // another alternative syntax, in case you had a scopeDraft() on your model:
                // $this->crud->addClause('draft'); 
            }
        );

        $this->crud->addFilter(
            [ // add a "simple" filter called Draft 
                'type' => 'simple',
                'name' => 'from_admin',
                'label' => 'No son de admin'
            ],
            false, // the simple filter has no values, just the "Draft" label specified above
            function () { // if the filter is active (the GET parameter "draft" exits)
                $this->crud->addClause('where', 'from_admin', '0');
                // we've added a clause to the CRUD so that only elements with draft=1 are shown in the table
                // an alternative syntax to this would have been
                // $this->crud->query = $this->crud->query->where('draft', '1'); 
                // another alternative syntax, in case you had a scopeDraft() on your model:
                // $this->crud->addClause('draft'); 
            }
        );


        /*
        |--------------------------------------------------------------------------
        | CrudPanel Configuration
        |--------------------------------------------------------------------------
        */

        $this->crud->addColumn([
            'label' => "Comentario",
            'name' => 'comment',
        ]);

        /*$this->crud->addColumn([
            'label' => "Receta", // Table column heading
            'type' => "select",
            'name' => 'receta_id', // the column that contains the ID of that connected entity;
            'entity' => 'recipe', // the method that defines the relationship in your Model
            'attribute' => "titulo", // foreign key attribute that is shown to user
            'model' => "App\Models\Receta", // foreign key model
        ]);*/

        $this->crud->addColumn([
            'label' => "Receta",
            'name' => 'recipe_name',
            'type' => 'text',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('recipes', function($q) use ($searchTerm){
                    $q->where('recetas.titulo', 'like', '%'.$searchTerm.'%');
                });
            }
        ]);

        $this->crud->addColumn([
            'label' => "¿Es respuesta?",
            'name' => 'is_a_response',
            'type' => 'boolean'
        ]);

        $this->crud->addColumn([
            'label' => "¿Respondido?",
            'name' => 'answered',
            'type' => 'boolean'
        ]);

        /*$this->crud->addColumn([
            'label' => "Usuario", // Table column heading
            'type' => "select",
            'name' => 'user_id', // the column that contains the ID of that connected entity;
            'entity' => 'user', // the method that defines the relationship in your Model
            'attribute' => "full_name", // foreign key attribute that is shown to user
            'model' => "App\User", // foreign key model
            'search_logic' => 'text'
        ]);*/

        // TODO: remove setFromDb() and manually define Fields and Columns
        // $this->crud->setFromDb();

        //if (Route::currentRouteName() != "crud.comment.index") {
            $comment  = $this->crud->model->find(Route::current()->parameter('comment'));
        //}

        if (Route::current()->parameter('comment')) {
            $this->crud->addField([   // CustomHTML
                'name' => 'separator',
                'type' => 'custom_html',
                'value' => '<h1>' . $comment->recipe_name . ' </h1>'
            ]);

            $this->crud->addField([
                'name' => 'receta_id',
                'type' => 'textarea',
                'attributes' => [
                    'readonly' => 'readonly',
                ],
            ]);

            $this->crud->addField([
                'label' => "Comentario",
                'name' => 'comment',
                'type' => 'textarea',
                'attributes' => [
                    'readonly' => 'readonly',
                ],
            ]);


            $this->crud->addField([
                'label' => "Respuesta",
                'name' => 'response',
                'type' => 'textarea',
                'default' => '@' . $comment->user->username . ' '
            ]);
        }

        // add asterisk for fields that are required in CommentRequest
        $this->crud->setRequiredFields(StoreRequest::class, 'create');
        $this->crud->setRequiredFields(UpdateRequest::class, 'edit');
    }

    public function store(StoreRequest $request)
    {
        // your additional operations before save here
        $redirect_location = parent::storeCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function update(UpdateRequest $request)
    {
        $response = $request->request->get('response');
        $receta_id = $request->request->get('receta_id');

        $request->request->remove('receta_id');
        $request->request->remove('response');

        $response_comment = Comment::create(['comment' => $response, 'is_a_response' => 1, 'user_id' => 2, 'from_admin' => 1, 'receta_id' => $receta_id]);
        DB::table('comment_receta')->insert(['receta_id' => $receta_id, 'comment_id' => $response_comment->id]);

        // your additional operations before save here
        $redirect_location = parent::updateCrud($request);
        // your additional operations after save here
        // use $this->data['entry'] or $this->crud->entry
        return $redirect_location;
    }

    public function recipeOptions(Request $request)
    {
        $term = $request->input('term');
        $options = Receta::where('titulo', 'like', '%' . $term . '%')->get()->pluck('name', 'id');
        return $options;
    }
}
