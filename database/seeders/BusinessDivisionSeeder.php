<?php

namespace Database\Seeders;

use App\Models\BusinessDivision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BusinessDivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $divisions = [
            [
                'name_en' => 'Zero Gravities Technologies',
                'name_bn' => 'জিরো গ্র্যাভিটিস টেকনোলজিস',
                'short_description_en' => 'Innovative software and web solutions.',
                'short_description_bn' => 'উদ্ভাবনী সফটওয়্যার এবং ওয়েব সমাধান।',
                'display_order' => 1,
            ],
            [
                'name_en' => 'Zero Gravities Managed IT',
                'name_bn' => 'জিরো গ্র্যাভিটিস ম্যানেজড আইটি',
                'short_description_en' => 'Comprehensive IT support and AMC services.',
                'short_description_bn' => 'সম্পূর্ণ আইটি সাপোর্ট এবং এএমসি পরিষেবা।',
                'display_order' => 2,
            ],
            [
                'name_en' => 'Zero Gravities Tech',
                'name_bn' => 'জিরো গ্র্যাভিটিস টেক',
                'short_description_en' => 'Corporate IT supply and infrastructure.',
                'short_description_bn' => 'কর্পোরেট আইটি সরবরাহ এবং অবকাঠামো।',
                'display_order' => 3,
            ],
            [
                'name_en' => 'Zero Gravities Cloud & Cyber Security',
                'name_bn' => 'জিরো গ্র্যাভিটিস ক্লাউড ও সাইবার সিকিউরিটি',
                'short_description_en' => 'Cloud migration, security audits, and cyber protection.',
                'short_description_bn' => 'ক্লাউড মাইগ্রেশন, নিরাপত্তা অডিট এবং সাইবার সুরক্ষা।',
                'display_order' => 4,
            ],
            [
                'name_en' => 'Zero Gravities IT Consulting',
                'name_bn' => 'জিরো গ্র্যাভিটিস আইটি কনসাল্টিং',
                'short_description_en' => 'Strategic IT advisory and digital transformation planning.',
                'short_description_bn' => 'কৌশলগত আইটি পরামর্শ এবং ডিজিটাল ট্রান্সফরমেশন পরিকল্পনা।',
                'display_order' => 5,
            ],
        ];

        foreach ($divisions as $division) {
            BusinessDivision::withTrashed()->updateOrCreate(
                ['slug_en' => Str::slug($division['name_en'])],
                [
                    'name_en' => $division['name_en'],
                    'name_bn' => $division['name_bn'],
                    'slug_bn' => Str::slug($division['name_bn']),
                    'short_description_en' => $division['short_description_en'],
                    'short_description_bn' => $division['short_description_bn'],
                    'display_order' => $division['display_order'],
                    'is_active' => true,
                    'deleted_at' => null,
                ]
            );
        }

        BusinessDivision::where('slug_en', 'zero-gravities-commerce')->delete();
    }
}
