<?php

$dir = 'amc_packages';
$title = 'AMC Packages';
$route = 'admin.amc-packages';
$var = 'packages';
$item = 'amcPackage';
$fields = ['name_en' => 'Name (EN)', 'price' => 'Price', 'is_active' => 'Status'];

$path = __DIR__."/resources/views/admin/$dir";
if (! is_dir($path)) {
    mkdir($path, 0777, true);
}

// Index
$index = "@extends('admin.layouts.admin')\n@section('title', '$title')\n@section('content')\n<div class=\"card border-0 shadow-sm\">\n<div class=\"card-header bg-white py-3 d-flex justify-content-between align-items-center\">\n<h5 class=\"mb-0 fw-bold\">$title</h5>\n<a href=\"{{ route('$route.create') }}\" class=\"btn btn-primary\"><i class=\"bi bi-plus-lg me-1\"></i> Add New</a>\n</div>\n<div class=\"card-body\">\n<div class=\"table-responsive\">\n<table class=\"table table-hover align-middle\">\n<thead>\n<tr>\n<th>#</th>\n";
foreach ($fields as $label) {
    $index .= "<th>$label</th>\n";
}
$index .= "<th width=\"150\">Actions</th>\n</tr>\n</thead>\n<tbody>\n@forelse(\$$var as \$$item)\n<tr>\n<td>{{ \$loop->iteration }}</td>\n";
foreach ($fields as $key => $label) {
    if ($key == 'is_active') {
        $index .= "<td><span class=\"badge bg-{{ \${$item}->$key ? 'success' : 'danger' }}\">{{ \${$item}->$key ? 'Active' : 'Inactive' }}</span></td>\n";
    } else {
        $index .= "<td>{{ \${$item}->$key }}</td>\n";
    }
}
$index .= "<td>\n<a href=\"{{ route('$route.edit', \$$item) }}\" class=\"btn btn-sm btn-outline-secondary\"><i class=\"bi bi-pencil\"></i></a>\n<form action=\"{{ route('$route.destroy', \$$item) }}\" method=\"POST\" class=\"d-inline\" onsubmit=\"return confirm('Are you sure?');\">\n@csrf\n@method('DELETE')\n<button type=\"submit\" class=\"btn btn-sm btn-outline-danger\"><i class=\"bi bi-trash\"></i></button>\n</form>\n</td>\n</tr>\n@empty\n<tr><td colspan=\"10\" class=\"text-center py-4\">No records found.</td></tr>\n@endforelse\n</tbody>\n</table>\n</div>\n</div>\n</div>\n@endsection";
file_put_contents("$path/index.blade.php", $index);

// Create
$create = "@extends('admin.layouts.admin')\n@section('title', 'Add Package')\n@section('content')\n<div class=\"card border-0 shadow-sm\">\n<div class=\"card-header bg-white py-3 d-flex justify-content-between align-items-center\">\n<h5 class=\"mb-0 fw-bold\">Add Package</h5>\n<a href=\"{{ route('$route.index') }}\" class=\"btn btn-outline-secondary\"><i class=\"bi bi-arrow-left me-1\"></i> Back</a>\n</div>\n<div class=\"card-body\">\n<form action=\"{{ route('$route.store') }}\" method=\"POST\">\n@csrf\n<div class=\"row mb-3\">\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (EN) *</label>\n<input type=\"text\" name=\"name_en\" class=\"form-control\" required>\n</div>\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (BN)</label>\n<input type=\"text\" name=\"name_bn\" class=\"form-control\">\n</div>\n</div>\n<div class=\"row mb-3\">\n<div class=\"col-md-4\">\n<label class=\"form-label\">Price</label>\n<input type=\"number\" name=\"price\" step=\"0.01\" class=\"form-control\">\n</div>\n<div class=\"col-md-4\">\n<label class=\"form-label\">Billing Cycle</label>\n<input type=\"text\" name=\"billing_cycle\" class=\"form-control\">\n</div>\n<div class=\"col-md-4\">\n<label class=\"form-label\">Order</label>\n<input type=\"number\" name=\"display_order\" class=\"form-control\" value=\"0\">\n</div>\n</div>\n<div class=\"row mb-3\">\n<div class=\"col-md-6 d-flex align-items-end\">\n<div class=\"form-check mb-2 me-3\">\n<input type=\"hidden\" name=\"is_active\" value=\"0\">\n<input class=\"form-check-input\" type=\"checkbox\" name=\"is_active\" value=\"1\" checked>\n<label class=\"form-check-label\">Active</label>\n</div>\n<div class=\"form-check mb-2\">\n<input type=\"hidden\" name=\"is_recommended\" value=\"0\">\n<input class=\"form-check-input\" type=\"checkbox\" name=\"is_recommended\" value=\"1\">\n<label class=\"form-check-label\">Recommended</label>\n</div>\n</div>\n</div>\n<button type=\"submit\" class=\"btn btn-primary\">Save</button>\n</form>\n</div>\n</div>\n@endsection";
file_put_contents("$path/create.blade.php", $create);

// Edit
$edit = "@extends('admin.layouts.admin')\n@section('title', 'Edit Package')\n@section('content')\n<div class=\"card border-0 shadow-sm\">\n<div class=\"card-header bg-white py-3 d-flex justify-content-between align-items-center\">\n<h5 class=\"mb-0 fw-bold\">Edit Package</h5>\n<a href=\"{{ route('$route.index') }}\" class=\"btn btn-outline-secondary\"><i class=\"bi bi-arrow-left me-1\"></i> Back</a>\n</div>\n<div class=\"card-body\">\n<form action=\"{{ route('$route.update', \${$item}) }}\" method=\"POST\">\n@csrf\n@method('PUT')\n<div class=\"row mb-3\">\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (EN) *</label>\n<input type=\"text\" name=\"name_en\" class=\"form-control\" value=\"{{ \${$item}->name_en }}\" required>\n</div>\n<div class=\"col-md-6\">\n<label class=\"form-label\">Name (BN)</label>\n<input type=\"text\" name=\"name_bn\" class=\"form-control\" value=\"{{ \${$item}->name_bn }}\">\n</div>\n</div>\n<div class=\"row mb-3\">\n<div class=\"col-md-4\">\n<label class=\"form-label\">Price</label>\n<input type=\"number\" name=\"price\" step=\"0.01\" class=\"form-control\" value=\"{{ \${$item}->price }}\">\n</div>\n<div class=\"col-md-4\">\n<label class=\"form-label\">Billing Cycle</label>\n<input type=\"text\" name=\"billing_cycle\" class=\"form-control\" value=\"{{ \${$item}->billing_cycle }}\">\n</div>\n<div class=\"col-md-4\">\n<label class=\"form-label\">Order</label>\n<input type=\"number\" name=\"display_order\" class=\"form-control\" value=\"{{ \${$item}->display_order }}\">\n</div>\n</div>\n<div class=\"row mb-3\">\n<div class=\"col-md-6 d-flex align-items-end\">\n<div class=\"form-check mb-2 me-3\">\n<input type=\"hidden\" name=\"is_active\" value=\"0\">\n<input class=\"form-check-input\" type=\"checkbox\" name=\"is_active\" value=\"1\" {{ \${$item}->is_active ? 'checked' : '' }}>\n<label class=\"form-check-label\">Active</label>\n</div>\n<div class=\"form-check mb-2\">\n<input type=\"hidden\" name=\"is_recommended\" value=\"0\">\n<input class=\"form-check-input\" type=\"checkbox\" name=\"is_recommended\" value=\"1\" {{ \${$item}->is_recommended ? 'checked' : '' }}>\n<label class=\"form-check-label\">Recommended</label>\n</div>\n</div>\n</div>\n<button type=\"submit\" class=\"btn btn-primary\">Update</button>\n</form>\n</div>\n</div>\n@endsection";
file_put_contents("$path/edit.blade.php", $edit);

echo "AmcPackage views generated.\n";
