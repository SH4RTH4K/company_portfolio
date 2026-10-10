<?php

$resources = [
    'product_categories' => ['title' => 'Product Categories', 'route' => 'admin.product-categories', 'var' => 'categories', 'item' => 'category', 'fields' => ['name_en' => 'Name (EN)', 'display_order' => 'Order', 'is_active' => 'Status']],
    'product_brands' => ['title' => 'Product Brands', 'route' => 'admin.product-brands', 'var' => 'brands', 'item' => 'brand', 'fields' => ['name_en' => 'Name (EN)', 'display_order' => 'Order', 'is_active' => 'Status']],
];

foreach ($resources as $dir => $data) {
    $path = __DIR__."/resources/views/admin/$dir";
    if (! is_dir($path)) {
        mkdir($path, 0777, true);
    }

    // Index
    $index = "@extends('admin.layouts.admin')\n@section('title', '{$data['title']}')\n@section('content')\n<div class=\"card border-0 shadow-sm\">\n<div class=\"card-header bg-white py-3 d-flex justify-content-between align-items-center\">\n<h5 class=\"mb-0 fw-bold\">{$data['title']}</h5>\n<a href=\"{{ route('{$data['route']}.create') }}\" class=\"btn btn-primary\"><i class=\"bi bi-plus-lg me-1\"></i> Add New</a>\n</div>\n<div class=\"card-body\">\n<div class=\"table-responsive\">\n<table class=\"table table-hover align-middle\">\n<thead>\n<tr>\n<th>#</th>\n";
    foreach ($data['fields'] as $label) {
        $index .= "<th>$label</th>\n";
    }
    $index .= "<th width=\"150\">Actions</th>\n</tr>\n</thead>\n<tbody>\n@forelse(\${$data['var']} as \${$data['item']})\n<tr>\n<td>{{ \$loop->iteration }}</td>\n";
    foreach ($data['fields'] as $key => $label) {
        if ($key == 'is_active') {
            $index .= "<td><span class=\"badge bg-{{ \${$data['item']}->$key ? 'success' : 'danger' }}\">{{ \${$data['item']}->$key ? 'Active' : 'Inactive' }}</span></td>\n";
        } else {
            $index .= "<td>{{ \${$data['item']}->$key }}</td>\n";
        }
    }
    $index .= "<td>\n<a href=\"{{ route('{$data['route']}.edit', \${$data['item']}) }}\" class=\"btn btn-sm btn-outline-secondary\"><i class=\"bi bi-pencil\"></i></a>\n<form action=\"{{ route('{$data['route']}.destroy', \${$data['item']}) }}\" method=\"POST\" class=\"d-inline\" onsubmit=\"return confirm('Are you sure?');\">\n@csrf\n@method('DELETE')\n<button type=\"submit\" class=\"btn btn-sm btn-outline-danger\"><i class=\"bi bi-trash\"></i></button>\n</form>\n</td>\n</tr>\n@empty\n<tr><td colspan=\"10\" class=\"text-center py-4\">No records found.</td></tr>\n@endforelse\n</tbody>\n</table>\n</div>\n</div>\n</div>\n@endsection";
    file_put_contents("$path/index.blade.php", $index);

    // Create
    $create = "@extends('admin.layouts.admin')\n@section('title', 'Add {$data['item']}')\n@section('content')\n<div class=\"card border-0 shadow-sm\">\n<div class=\"card-header bg-white py-3 d-flex justify-content-between align-items-center\">\n<h5 class=\"mb-0 fw-bold\">Add {$data['item']}</h5>\n<a href=\"{{ route('{$data['route']}.index') }}\" class=\"btn btn-outline-secondary\"><i class=\"bi bi-arrow-left me-1\"></i> Back</a>\n</div>\n<div class=\"card-body\">\n<form action=\"{{ route('{$data['route']}.store') }}\" method=\"POST\">\n@csrf\n<div class=\"row mb-3\">\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (EN) *</label>\n<input type=\"text\" name=\"name_en\" class=\"form-control\" required>\n</div>\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (BN)</label>\n<input type=\"text\" name=\"name_bn\" class=\"form-control\">\n</div>\n</div>\n<div class=\"row mb-3\">\n<div class=\"col-md-6\">\n<label class=\"form-label\">Order</label>\n<input type=\"number\" name=\"display_order\" class=\"form-control\" value=\"0\">\n</div>\n<div class=\"col-md-6 d-flex align-items-end\">\n<div class=\"form-check mb-2\">\n<input type=\"hidden\" name=\"is_active\" value=\"0\">\n<input class=\"form-check-input\" type=\"checkbox\" name=\"is_active\" value=\"1\" checked>\n<label class=\"form-check-label\">Active</label>\n</div>\n</div>\n</div>\n<button type=\"submit\" class=\"btn btn-primary\">Save</button>\n</form>\n</div>\n</div>\n@endsection";
    file_put_contents("$path/create.blade.php", $create);

    // Edit
    $edit = "@extends('admin.layouts.admin')\n@section('title', 'Edit {$data['item']}')\n@section('content')\n<div class=\"card border-0 shadow-sm\">\n<div class=\"card-header bg-white py-3 d-flex justify-content-between align-items-center\">\n<h5 class=\"mb-0 fw-bold\">Edit {$data['item']}</h5>\n<a href=\"{{ route('{$data['route']}.index') }}\" class=\"btn btn-outline-secondary\"><i class=\"bi bi-arrow-left me-1\"></i> Back</a>\n</div>\n<div class=\"card-body\">\n<form action=\"{{ route('{$data['route']}.update', \${$data['item']}) }}\" method=\"POST\">\n@csrf\n@method('PUT')\n<div class=\"row mb-3\">\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (EN) *</label>\n<input type=\"text\" name=\"name_en\" class=\"form-control\" value=\"{{ \${$data['item']}->name_en }}\" required>\n</div>\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (BN)</label>\n<input type=\"text\" name=\"name_bn\" class=\"form-control\" value=\"{{ \${$data['item']}->name_bn }}\">\n</div>\n</div>\n<div class=\"row mb-3\">\n<div class=\"col-md-6\">\n<label class=\"form-label\">Order</label>\n<input type=\"number\" name=\"display_order\" class=\"form-control\" value=\"{{ \${$data['item']}->display_order }}\">\n</div>\n<div class=\"col-md-6 d-flex align-items-end\">\n<div class=\"form-check mb-2\">\n<input type=\"hidden\" name=\"is_active\" value=\"0\">\n<input class=\"form-check-input\" type=\"checkbox\" name=\"is_active\" value=\"1\" {{ \${$data['item']}->is_active ? 'checked' : '' }}>\n<label class=\"form-check-label\">Active</label>\n</div>\n</div>\n</div>\n<button type=\"submit\" class=\"btn btn-primary\">Update</button>\n</form>\n</div>\n</div>\n@endsection";
    file_put_contents("$path/edit.blade.php", $edit);
}

echo "Views generated.\n";
