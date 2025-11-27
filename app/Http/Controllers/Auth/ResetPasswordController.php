<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/recetario';

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
    // return your custom rules here
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ];
    }

    /**
     * Get the password reset validation error messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return [
            // Here write your custom validation error messages
            'token.required' => 'No token no honey!',
            'email.required' => 'El correo es requerido',
            'email.email' => 'El formato del correo no es correcto',
            'password.required' => 'La contraseña es requerida',
            'password.confirmed' => 'La confirmacion de contraseña no coincide',
            'password.min' => 'Proporcione una contraseña de al menos 6 caracteres.',
            
        ];
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }
    
}
