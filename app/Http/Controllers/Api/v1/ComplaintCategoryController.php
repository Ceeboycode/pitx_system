<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ComplaintCategory;

class ComplaintCategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            ComplaintCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id','name'])
        );
    }
}