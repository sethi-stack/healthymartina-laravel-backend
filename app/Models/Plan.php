<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Cviebrock\EloquentSluggable\Sluggable;



class Plan extends Model
{
    use CrudTrait;
    use SoftDeletes;
    use Sluggable;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    protected $table = 'planes';
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

    public function sluggable()
    {
        return [
            'slug' => [
                'source' => 'nombre'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

     public function recetas()
    {
        return $this->belongsToMany('App\Models\Receta', 'receta_planes', 'plan_id', 'receta_id');
    }

    public function plan_receta() {
        return $this->hasOne('App\Models\PlanReceta','plan_id');
    }


    public function tipo(){
        return $this->belongsTo('App\Models\Tipo');
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
    public function getIconoAttribute(){
        return \Storage::url($this->attributes['icono']);
    }

    public function setIconoAttribute($value)
    {
        $attribute_name = "icono";
       $disk = 'gcs'; // or use your own disk, defined in config/filesystems.php
        $destination_path = "planes/iconos"; // path relative to the disk above

        // if the image was erased
        if ($value==null) {
            // delete the image from disk
            \Storage::delete($this->{$attribute_name});

            // set null in the database column
            $this->attributes[$attribute_name] = null;
        }

        // if a base64 was sent, store it in the db
        if (starts_with($value, 'data:image'))
        {
            // 0. Make the image
            $image = \Image::make($value)->encode('jpg', 90);
            // 1. Generate a filename.
            $filename = md5($value.time()).'.jpg';
            // 2. Store the image on disk.
            \Storage::put($destination_path.'/'.$filename, $image->stream());
            // 3. Save the public path to the database
        // but first, remove "public/" from the path, since we're pointing to it from the root folder
        // that way, what gets saved in the database is the user-accesible URL
            $public_destination_path = \Str::replaceFirst('public/', '', $destination_path);
            $this->attributes[$attribute_name] = $public_destination_path.'/'.$filename;
        }
    }
    public function getGuiaAttribute(){
         if (isset($this->attributes['guia'])) {
            return \Storage::url($this->attributes['guia']);
         }
          return null;
    }

    public function setGuiaAttribute($value)
    {
        $attribute_name = "guia";
        $disk = 'gcs'; // or use your own disk, defined in config/filesystems.php
        $destination_path = "planes/iconos"; // path relative to the disk above

        $this->uploadFileToDisk($value, $attribute_name, $disk, $destination_path);
        return $this->attributes['guia'];
    }
}
