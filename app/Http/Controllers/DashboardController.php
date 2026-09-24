<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetrics $metrics,
    ) {}

    public function index(Request $request): View
    {
        if ($request->user()?->isAdmin()) {
            return view('dashboard', $this->metrics->forAdmin());
        }

        return view('dashboard-auditor', $this->metrics->forAuditor());
    }
}
