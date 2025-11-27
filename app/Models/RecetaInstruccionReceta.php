<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\CrudTrait;
use PhpUnitsOfMeasure\PhysicalQuantity\Volume;

class RecetaInstruccionReceta extends Model
{
    use CrudTrait;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'receta_instruccion_receta';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    //protected $fillable = [];
    // protected $hidden = [];
    // protected $dates = [];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */
    public function getGramosTotales(){
      $medida = $this->rirm->first();
      $gramos_instruccion = $this->instruccion->equivalencia_gramos;
      $total = 0;
      // dd($gramos_instruccion);

      //Si la medida de la instrucción, es diferente de la medida de la receta
      if($this->instruccion->medida_id != $medida->medida_id){
        if($this->instruccion->medida->tipoMedida->id == $medida->medida->tipoMedida->id && $medida->medida->tipoMedida->id == 1){
          $originalUnit = new Volume($medida->cantidad, $medida->medida->nombre_english);
          $newQuantity = $originalUnit->toUnit($this->instruccion->medida->nombre_english);
          // dd($originalUnit,$newQuantity,$this->instruccion->medida,$medida->medida);
          $cantidad = $newQuantity;
          $total+= $cantidad * $gramos_instruccion;
        }
        else{
          //dump('No compatibles', $this->instruccion->medida,$medida->medida);
        }
      }
      else{
        $cantidad = $medida->cantidad;
        $total += $cantidad * $gramos_instruccion;
      }
      // dd($medidas);

      // if(count($medidas)>1){
      //   dd($medidas);
      // }
      // dd($total);
      return $total;
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    public function receta(){
      return $this->belongsTo('App\Models\Receta', 'receta_id');
    }

    public function subreceta(){
      return $this->belongsTo('App\Models\Receta', 'subreceta_id');
    }

    public function medida(){
      return $this->belongsTo('App\Models\Medida', 'medida_id');
    }

    public function instruccion(){
      return $this->belongsTo('App\Models\Instruccion', 'instruccion_id')->withTrashed();
    }

    public function rirm(){
      return $this->hasMany('App\Models\RecetaInstruccionRecetaMedida', 'rec_inst_rec_id');
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
