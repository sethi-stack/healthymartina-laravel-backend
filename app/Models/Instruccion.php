<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Instruccion extends Model
{
    use CrudTrait;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'instrucciones';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    public function anadirButton(){
        if(request()->get("ingrediente")) {
            return '<a class="btn btn-primary btn-default" href="'.backpack_url('Instrucciones').'/create?ingrediente='.request()->get("ingrediente") .'" data-toggle="tooltip" title=""><i class="fa fa-plus"></i> Añadir Instrucción</a>';

        }
    }

    public function editarButton(){
        if(request()->get("ingrediente")) {
            return '<a class="btn btn-xs btn-default" href="'.backpack_url('Instrucciones').'/'.$this->id .'/edit'.'?ingrediente='.request()->get("ingrediente") .'" data-toggle="tooltip" title=""><i class="fa fa-edit"></i> Editar</a>';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function ingrediente() {
        return $this->belongsTo('App\Models\Ingrediente', 'ingrediente_id');
    }

    public function rir() {
        return $this->hasMany('App\Models\RecetaInstruccionReceta');
    }

    public function medida() {
        return $this->belongsTo('App\Models\Medida', 'medida_id');
    }

    public function equivalenciaMedida() {
        return $this->belongsTo('App\Models\Medida', 'equivalencia_medida_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
