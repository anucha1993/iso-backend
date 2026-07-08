<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FormCategory;
use Illuminate\Http\JsonResponse;

class FormRegisterController extends Controller
{
    /**
     * The form register (ทะเบียนแบบฟอร์ม) grouped by work-area/category.
     * Drives the category-grouped navigation and the register page.
     */
    public function index(): JsonResponse
    {
        $categories = FormCategory::with(['templates' => fn ($q) => $q->where('is_active', true)->orderBy('order')])
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json(['data' => $categories]);
    }
}
