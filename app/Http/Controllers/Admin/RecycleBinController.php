<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessDivision;
use App\Models\Industry;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Service;
use Illuminate\Http\Request;

class RecycleBinController extends Controller
{
    protected $models = [
        'services' => [
            'class' => Service::class,
            'label' => 'Services',
            'title_field' => 'title_en',
        ],
        'products' => [
            'class' => Product::class,
            'label' => 'Products',
            'title_field' => 'name_en',
        ],
        'product-categories' => [
            'class' => ProductCategory::class,
            'label' => 'Product Categories',
            'title_field' => 'name_en',
        ],
        'product-brands' => [
            'class' => ProductBrand::class,
            'label' => 'Product Brands',
            'title_field' => 'name_en',
        ],
        'posts' => [
            'class' => Post::class,
            'label' => 'Posts',
            'title_field' => 'title_en',
        ],
        'post-categories' => [
            'class' => PostCategory::class,
            'label' => 'Post Categories',
            'title_field' => 'name_en',
        ],
        'business-divisions' => [
            'class' => BusinessDivision::class,
            'label' => 'Business Divisions',
            'title_field' => 'name_en',
        ],
        'industries' => [
            'class' => Industry::class,
            'label' => 'Industries',
            'title_field' => 'title_en',
        ],
        'portfolios' => [
            'class' => Portfolio::class,
            'label' => 'Portfolios',
            'title_field' => 'project_name_en',
        ],
    ];

    public function index(Request $request)
    {
        $currentModule = $request->query('module', 'services');

        if (! array_key_exists($currentModule, $this->models)) {
            $currentModule = 'services';
        }

        $modelClass = $this->models[$currentModule]['class'];
        $titleField = $this->models[$currentModule]['title_field'];

        $trashedItems = $modelClass::onlyTrashed()->orderBy('deleted_at', 'desc')->get();

        return view('admin.recycle_bin.index', [
            'modules' => $this->models,
            'currentModule' => $currentModule,
            'trashedItems' => $trashedItems,
            'titleField' => $titleField,
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'id' => 'required|integer',
        ]);

        $module = $request->input('module');
        if (! array_key_exists($module, $this->models)) {
            return back()->with('error', 'Invalid module.');
        }

        $modelClass = $this->models[$module]['class'];
        $item = $modelClass::onlyTrashed()->findOrFail($request->input('id'));
        $item->restore();

        return back()->with('success', 'Item restored successfully.');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'module' => 'required|string',
            'id' => 'required|integer',
        ]);

        $module = $request->input('module');
        if (! array_key_exists($module, $this->models)) {
            return back()->with('error', 'Invalid module.');
        }

        $modelClass = $this->models[$module]['class'];
        $item = $modelClass::onlyTrashed()->findOrFail($request->input('id'));
        $item->forceDelete();

        return back()->with('success', 'Item permanently deleted.');
    }
}
