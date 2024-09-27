<?php declare(strict_types=1);

namespace App\Controllers;

use Lalaz\Http\Controller;

class HomeController extends Controller
{
    public function index($req, $res)
    {
        $res->render('home/index', [
            'title' => 'Lalaz | Easy Development, Simple Deployment'
        ]);
    }
}
