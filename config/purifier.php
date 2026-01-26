<?php

return [
    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => storage_path('app/purifier'),
    'cacheFileMode' => 0755,
    'settings' => [
        'product_description' => [
            'HTML.Allowed' => 'p,br,strong,em,ul,ol,li,a[href|title|target],h2,h3,blockquote,img[src|alt|title|width|height]',
            'CSS.AllowedProperties' => [],
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
            'HTML.ForbiddenElements' => ['script', 'style'],
            'Attr.EnableID' => false,
            'URI.AllowedSchemes' => [
                'http' => true,
                'https' => true,
                'mailto' => true,
            ],
        ],
    ],
];
