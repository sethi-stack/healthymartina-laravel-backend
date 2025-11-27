<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
//use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;


class Membresia extends Model
{
    
    //use CrudTrait;
    use SoftDeletes;
    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $connection = 'mysql_2';

    protected $table = 'membresias';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
     protected $guarded = ['id'];
    //protected $fillable = ['nombre', 'precio'];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    public function detallesButton(){
        return '<a class="btn btn-xs btn-default" href="'.backpack_url('detallemembresia').'?membresia='.$this->id.'" data-toggle="tooltip" title=""><i class="fa fa-book"></i> Detalles</a>';
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function detalles(){
        return $this->hasMany('App\Models\DetalleMembresia', 'membresia_id');
    }
    public function clientes() {
        return $this->hasMany('App\Models\Cliente', 'membresia_id');
    }
    public function role() {
        return $this->setConnection('mysql')->belongsTo('App\Role', 'nombre','name');
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
