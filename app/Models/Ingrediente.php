<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingrediente extends Model
{
    use CrudTrait;
    use SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'ingredientes';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
     protected $guarded = ['id'];
    //protected $fillable = ['nombre', 'usda'];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    
    public function resetIngredientes(){
        $ingredientes = Ingrediente::all();
        foreach($ingredientes as $ingrediente){
            $ingrediente->nombre = '1'.$ingrediente->nombre;
            $ingrediente->save();
        }
        //return '<a class="btn btn-sm btn-default"  data-toggle="tooltip" title=""><i class=""></i> qqq</a>';
    }
    public function instruccionButton(){
        return '<a class="btn btn-sm btn-default" href="'.backpack_url('Instrucciones').'?ingrediente='. $this->id .'" data-toggle="tooltip" title=""><i class=""></i> Ver instrucciones</a>';
    }

    public function medidasButton(){
        return '<a class="btn btn-sm btn-default" href="'.backpack_url('Medidas').'?medida='. $this->id .'" data-toggle="tooltip" title=""><i class=""></i> Ver medidas</a>';
    }

    public function deleteOnes($formato = false){
        $ingredientes = Ingrediente::all();
        foreach($ingredientes as $ingrediente){
            if($ingrediente->nombre[0] == 1)
                $ingrediente->delete();
            $ingrediente->save();
        }
        return $ingrediente->nombre;
    }

    public function getApareceEn(){
        $instrucciones = $this->instrucciones;
        $contador = 0;
        foreach($instrucciones as $instruccion){
            $contador += $instruccion->rir->count();
        }
        return $contador;
    }

    public function getRecetasButton($crud = false){
        // return 'hp;aADSKLFAKLJSDFJKLASFJKLASLKJFAJKLDSFJKLASFKLJASDLJKFLKJSAFJKLASLJKFAJNKLSFJKLASFKLJASJKLFKLJFS';
        // return '<a class="btn btn-xs btn-success" href="javascript:void(0)" onclick="repButton(this,'.$this->id.')" data-toggle="tooltip" target="_blank" title=""><i class="fa fa-credit-card"></i> REP</a>';

        return '<a class="btn btn-xs btn-success" href="'.backpack_url('RecetaInstruccionReceta').'?ingrediente_id='.$this->id.'" data-toggle="tooltip" target="_blank" title="1"><i class="fa fa-paper-plane"></i> Recetas</a>';
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    // public function ingredientesRecetas(){
    //   return $this->hasMany('App\Models\IngredienteReceta', 'ingrediente_id');
    // }

    /*public function unidadMedida(){
      return $this->belongsTo('App\Models\UnidadMedida', 'unidad_medida_id');
    }*/

    // public function tiposMedida() {
    //     return $this->belongsToMany('App\Models\TipoMedida', 'ingrediente_tipo_medida', 'ingrediente_id', 'tipo_medida_id');
    // }

    // public function medidas() {
    //     return $this->belongsToMany('App\Models\Medida', 'medida_id');
    // }

    public function categoria() {
        return $this->belongsTo('App\Models\Categoria', 'categoria_id');
    }

    public function receta() {
        return $this->belongsToMany('App\Models\Receta');
    }

    public function forma_compra() {
        return $this->belongsTo('App\Models\Medida', 'forma_compra_id');
    }

    public function tipo_medida() {
        return $this->belongsTo('App\Models\TipoMedida', 'tipo_medida_id');
    }


    // public function equivalenciaMedida() {
    //     return $this->belongsTo('App\Models\Medida', 'equivalencia_medida_id');
    // }

    


    public function instrucciones() {
        return $this->hasMany('App\Models\Instruccion');
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

    /*public function getNombreAttribute()
    {
        //return ucfirst(strtolower($this->attributes['nombre']));
    }*/
}
