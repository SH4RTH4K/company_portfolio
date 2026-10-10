<?php

$models = [
    'BusinessDivision',
    'Service',
    'ProjectCategory',
    'Portfolio',
    'Industry',
    'ProductCategory',
    'ProductBrand',
    'Product',
    'AmcPackage',
    'Client',
    'Testimonial',
    'Partner',
    'Team',
    'PostCategory',
    'Post',
    'Lead',
    'SiteSetting',
    'SeoMeta',
];

foreach ($models as $model) {
    echo "Creating $model...\n";
    shell_exec("php artisan make:model $model -m");
}
echo "Done scaffolding.\n";
