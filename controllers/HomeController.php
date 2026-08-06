<?php
require_once __DIR__ . '/../includes/Controller.php';

class HomeController extends Controller
{
    public function index(): void
    {
        $stats = publicHomeStats();
        $this->view('home/index', compact('stats'), 'main');
    }
}
