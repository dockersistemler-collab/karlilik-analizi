<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class EInvoiceApiDocsController extends Controller
{
    public function show(Request $request): Response
    {
        $markdownPath = $this->safeDocsPath('docs/api/einvoice-api-tr.md');
        if (!$markdownPath || !File::exists($markdownPath)) {
            return response()->view('admin.docs.einvoice-api', [
                'title' => 'E-Fatura API Dokümantasyonu',
                'html' => null,
                'error' => 'Dokümantasyon dosyası bulunamadı: docs/api/einvoice-api-tr.md',
            ]);
        }
$markdown = (string) File::get($markdownPath);

        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
            ],
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new HeadingPermalinkExtension());

        $converter = new MarkdownConverter($environment);

        $html = (string) $converter->convert($markdown);

        return response()->view('admin.docs.einvoice-api', [
            'title' => 'E-Fatura API Dokümantasyonu',
            'html' => $html,
            'error' => null,
        ]);
    }

    public function downloadOpenApi(): Response|BinaryFileResponse
    {
        $path = $this->safeDocsPath('docs/api/einvoice-api-openapi.yaml');
        if (!$path || !File::exists($path)) {
            return response()->view('admin.docs.einvoice-api', [
                'title' => 'E-Fatura API Dokümantasyonu',
                'html' => null,
                'error' => 'OpenAPI dosyası bulunamadı: docs/api/einvoice-api-openapi.yaml',
            ]);
        }

        return response()->download($path, 'einvoice-api-openapi.yaml', [
            'Content-Type' => 'application/yaml; charset=UTF-8',
        ]);
    }

    public function downloadPostman(): Response|BinaryFileResponse
    {
        $path = $this->safeDocsPath('docs/api/postman/einvoice-api.postman_collection.json');
        if (!$path || !File::exists($path)) {
            return response()->view('admin.docs.einvoice-api', [
                'title' => 'E-Fatura API Dokümantasyonu',
                'html' => null,
                'error' => 'Postman collection dosyası bulunamadı: docs/api/postman/einvoice-api.postman_collection.json',
            ]);
        }

        return response()->download($path, 'einvoice-api.postman_collection.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }

    private function safeDocsPath(string $relativePath): ?string
    {
        $base = base_path();
        $fullPath = $base.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        $realDocsRoot = realpath($base.DIRECTORY_SEPARATOR.'docs');
        $realTarget = realpath($fullPath);

        if (!$realDocsRoot) {
            return null;
        }

        if ($realTarget === false) {
            return $fullPath;
        }
$realDocsRoot = rtrim($realDocsRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (!str_starts_with($realTarget, $realDocsRoot)) {
            return null;
        }

        return $realTarget;
    }
}
