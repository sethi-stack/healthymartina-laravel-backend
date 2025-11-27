<?php

namespace App\Http\Controllers;

use App\Models\PrivacyNotice;
use App\Models\TermsConditions;
use Illuminate\Http\Request;

class LegalDocsController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function showTerms()
    {
        $terms = TermsConditions::whereActive(1)->first();
        return view('terminos-condiciones', compact('terms'));
    }

    public function showPrivacyNotice()
    {
        $notice = PrivacyNotice::whereActive(1)->first();
        return view('privacy', compact('notice'));
    }
}
