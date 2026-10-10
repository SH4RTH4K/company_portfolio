<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name',     'value_en' => 'Zero Gravities Ltd.',           'value_bn' => 'জিরো গ্রাভিটিজ লিমিটেড'],
            ['key' => 'tagline',          'value_en' => 'Technology • Solutions • Commerce', 'value_bn' => 'প্রযুক্তি • সমাধান • বাণিজ্য'],
            ['key' => 'phone',            'value_en' => '+880 1400-000000',               'value_bn' => null],
            ['key' => 'whatsapp',         'value_en' => '+8801400000000',                 'value_bn' => null],  // No spaces/dashes for WA link
            ['key' => 'email',            'value_en' => 'info@zerogravities.com',         'value_bn' => null],
            ['key' => 'address',          'value_en' => 'Dhaka, Bangladesh',              'value_bn' => 'ঢাকা, বাংলাদেশ'],
            ['key' => 'company_phone',    'value_en' => '+880 1400-000000',               'value_bn' => null],
            ['key' => 'company_email',    'value_en' => 'info@zerogravities.com',         'value_bn' => null],
            ['key' => 'company_address',  'value_en' => 'Dhaka, Bangladesh',              'value_bn' => 'ঢাকা, বাংলাদেশ'],
            ['key' => 'site_name',        'value_en' => 'Zero Gravities',                 'value_bn' => 'জিরো গ্রাভিটিজ'],
            ['key' => 'tagline_en',       'value_en' => 'Technology • Solutions • Commerce', 'value_bn' => null],
            ['key' => 'tagline_bn',       'value_en' => 'প্রযুক্তি • সমাধান • বাণিজ্য',    'value_bn' => null],
            ['key' => 'facebook',         'value_en' => 'https://facebook.com/zerogravities', 'value_bn' => null],
            ['key' => 'linkedin',         'value_en' => 'https://linkedin.com/company/zerogravities', 'value_bn' => null],
            ['key' => 'youtube',          'value_en' => '',                               'value_bn' => null],
            ['key' => 'twitter',          'value_en' => '',                               'value_bn' => null],
            ['key' => 'default_language', 'value_en' => 'en',                             'value_bn' => null],
            ['key' => 'development_mode_enabled', 'value_en' => '0',                       'value_bn' => null],
            ['key' => 'development_mode_badge', 'value_en' => 'Development Mode',          'value_bn' => null],
            ['key' => 'development_mode_title', 'value_en' => 'We are crafting something better', 'value_bn' => null],
            ['key' => 'development_mode_message', 'value_en' => 'Zero Gravities is upgrading the experience. Please check back soon, or contact our team for urgent support.', 'value_bn' => null],
            ['key' => 'development_mode_eta', 'value_en' => 'Expected back shortly',        'value_bn' => null],
            ['key' => 'development_mode_cta_label', 'value_en' => 'Contact Support',        'value_bn' => null],
            ['key' => 'development_mode_cta_url', 'value_en' => 'mailto:info@zerogravities.com', 'value_bn' => null],
            ['key' => 'development_mode_design', 'value_en' => 'orbit',                     'value_bn' => null],
            ['key' => 'development_mode_background_color', 'value_en' => '#0d1b2a',         'value_bn' => null],
            ['key' => 'development_mode_accent_color', 'value_en' => '#00b4d8',             'value_bn' => null],
            ['key' => 'development_mode_text_color', 'value_en' => '#ffffff',               'value_bn' => null],
            ['key' => 'logo',             'value_en' => null,                             'value_bn' => null],
            ['key' => 'logo_width',       'value_en' => '180',                            'value_bn' => null],
            ['key' => 'logo_height',      'value_en' => '54',                             'value_bn' => null],
            ['key' => 'logo_padding',     'value_en' => '0',                              'value_bn' => null],
            ['key' => 'logo_border_radius', 'value_en' => '0',                            'value_bn' => null],
            ['key' => 'logo_background_color', 'value_en' => 'transparent',                'value_bn' => null],
            ['key' => 'text_logo_icon_class', 'value_en' => 'bi-rocket-takeoff-fill',      'value_bn' => null],
            ['key' => 'text_logo_icon_size', 'value_en' => '32',                            'value_bn' => null],
            ['key' => 'text_logo_icon_color', 'value_en' => '#0d6efd',                     'value_bn' => null],
            ['key' => 'text_logo_primary_text', 'value_en' => 'ZERO',                      'value_bn' => null],
            ['key' => 'text_logo_primary_color', 'value_en' => '#ffffff',                  'value_bn' => null],
            ['key' => 'text_logo_accent_text', 'value_en' => 'GRAVITIES',                  'value_bn' => null],
            ['key' => 'text_logo_accent_color', 'value_en' => '#00b4d8',                   'value_bn' => null],
            ['key' => 'text_logo_tagline_text', 'value_en' => 'Beyond Limits',              'value_bn' => null],
            ['key' => 'text_logo_tagline_color', 'value_en' => '#8fa6b8',                   'value_bn' => null],
            ['key' => 'text_logo_tagline_size', 'value_en' => '11',                         'value_bn' => null],
            ['key' => 'favicon',          'value_en' => null,                             'value_bn' => null],
            ['key' => 'apple_touch_icon', 'value_en' => null,                             'value_bn' => null],
            ['key' => 'default_og_image', 'value_en' => null,                             'value_bn' => null],
            ['key' => 'footer_scripts',   'value_en' => '',                               'value_bn' => null],
            ['key' => 'head_scripts',     'value_en' => '',                               'value_bn' => null],
            ['key' => 'ga_tracking_id',   'value_en' => '',                               'value_bn' => null],
            ['key' => 'gtm_id',           'value_en' => '',                               'value_bn' => null],
            ['key' => 'robots_txt',       'value_en' => "User-agent: *\nAllow: /\nSitemap: ".url('/sitemap.xml'), 'value_bn' => null],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(['key' => $setting['key']], [
                'value_en' => $setting['value_en'],
                'value_bn' => $setting['value_bn'],
            ]);
        }

        // Seed FAQ data
        $faqs = [
            [
                'question_en' => 'What is an Annual Maintenance Contract (AMC)?',
                'question_bn' => 'বার্ষিক রক্ষণাবেক্ষণ চুক্তি (AMC) কী?',
                'answer_en' => 'An Annual Maintenance Contract (AMC) is a service agreement between Zero Gravities and your organization that covers scheduled preventive maintenance, priority incident response, and technical support for your IT infrastructure throughout the year.',
                'answer_bn' => 'বার্ষিক রক্ষণাবেক্ষণ চুক্তি (AMC) হলো জিরো গ্রাভিটিজ এবং আপনার প্রতিষ্ঠানের মধ্যে একটি সেবা চুক্তি যা বছরব্যাপী আইটি অবকাঠামোর নির্ধারিত প্রতিরোধমূলক রক্ষণাবেক্ষণ, অগ্রাধিকার ঘটনা প্রতিক্রিয়া এবং প্রযুক্তি সহায়তা অন্তর্ভুক্ত করে।',
                'category' => 'AMC',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'question_en' => 'What response time can I expect with an AMC?',
                'question_bn' => 'AMC-তে আমি কতক্ষণের মধ্যে সাড়া পাব?',
                'answer_en' => 'Our AMC packages offer response times ranging from 2 hours (Critical SLA) to 4 hours (Standard SLA) for critical incidents. Remote support is available 24/7/365, and on-site response is guaranteed within 4 hours during business hours.',
                'answer_bn' => 'আমাদের AMC প্যাকেজগুলো জরুরি ঘটনার জন্য ২ ঘন্টা (ক্রিটিক্যাল SLA) থেকে ৪ ঘন্টা (স্ট্যান্ডার্ড SLA) পর্যন্ত প্রতিক্রিয়া সময় প্রদান করে। রিমোট সাপোর্ট ২৪/৭/৩৬৫ পাওয়া যায়।',
                'category' => 'AMC',
                'display_order' => 2,
                'is_active' => true,
            ],
            [
                'question_en' => 'What does Managed IT service include?',
                'question_bn' => 'ম্যানেজড আইটি সেবায় কী অন্তর্ভুক্ত?',
                'answer_en' => 'Our Managed IT service covers proactive network monitoring (24/7), server administration, security patch management, backup management, helpdesk support, and regular IT health reports. We act as your dedicated IT department.',
                'answer_bn' => 'আমাদের ম্যানেজড আইটি সেবায় রয়েছে প্রো-অ্যাকটিভ নেটওয়ার্ক মনিটরিং (২৪/৭), সার্ভার প্রশাসন, নিরাপত্তা প্যাচ ব্যবস্থাপনা, ব্যাকআপ ব্যবস্থাপনা, হেল্পডেস্ক সাপোর্ট এবং নিয়মিত আইটি স্বাস্থ্য রিপোর্ট।',
                'category' => 'Managed IT',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'question_en' => 'Do you provide hardware procurement and supply?',
                'question_bn' => 'আপনারা কি হার্ডওয়্যার সংগ্রহ ও সরবরাহ করেন?',
                'answer_en' => 'Yes. Zero Gravities is an authorized partner for leading brands including Dell, HP, Lenovo, Cisco, and Fortinet. We supply genuine enterprise hardware with valid warranties and provide pre-delivery testing and on-site installation.',
                'answer_bn' => 'হ্যাঁ। জিরো গ্রাভিটিজ Dell, HP, Lenovo, Cisco এবং Fortinet সহ শীর্ষস্থানীয় ব্র্যান্ডের অনুমোদিত পার্টনার। আমরা বৈধ ওয়ারেন্টিসহ আসল এন্টারপ্রাইজ হার্ডওয়্যার সরবরাহ করি।',
                'category' => 'Products',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'question_en' => 'Can you serve clients outside Dhaka?',
                'question_bn' => 'আপনারা কি ঢাকার বাইরে সেবা দেন?',
                'answer_en' => 'Yes. We serve enterprise clients across Bangladesh including Chittagong, Sylhet, Rajshahi, and beyond. Remote support is available nationwide, and our field engineers can be deployed to major divisional cities.',
                'answer_bn' => 'হ্যাঁ। আমরা চট্টগ্রাম, সিলেট, রাজশাহীসহ সারা বাংলাদেশে এন্টারপ্রাইজ ক্লায়েন্টদের সেবা দিই। রিমোট সাপোর্ট সারাদেশে পাওয়া যায়।',
                'category' => 'General',
                'display_order' => 1,
                'is_active' => true,
            ],
            [
                'question_en' => 'How do I get a quote or start a project?',
                'question_bn' => 'কীভাবে কোটেশন পাব বা প্রজেক্ট শুরু করব?',
                'answer_en' => 'Simply fill out the contact form on our website, call our hotline, or send us a WhatsApp message. Our team will schedule a discovery call within 24 hours to understand your requirements and provide a customized proposal.',
                'answer_bn' => 'আমাদের ওয়েবসাইটের যোগাযোগ ফর্ম পূরণ করুন, হটলাইনে কল করুন বা WhatsApp করুন। আমাদের টিম ২৪ ঘন্টার মধ্যে আপনার সাথে যোগাযোগ করবে।',
                'category' => 'General',
                'display_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }

        $this->command->info('Site settings and FAQs seeded successfully.');
    }
}
