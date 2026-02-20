<?php

namespace App\Services\CommissionTariffs;

use App\Imports\CommissionTariffPreviewImport;
use App\Jobs\CommissionTariffImportJob;
use App\Models\CommissionTariffUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class CommissionTariffImportService
{
    public function storeUpload(User $user, UploadedFile $file, ?string $marketplace = null): array
    {
        $path = $file->store('commission-tariffs');

        $upload = CommissionTariffUpload::create([
            'marketplace' => $marketplace,
            'file_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'uploaded_by' => $user->id,
            'status' => 'uploaded',
        ]);

        $preview = Excel::toArray(new CommissionTariffPreviewImport(), $path);
        $rows = $preview[0] ?? [];
        $headers = count($rows) > 0 ? array_keys($rows[0]) : [];

        return [
            'upload' => $upload,
            'headers' => $headers,
            'rows' => array_slice($rows, 0, 20),
        ];
    }

    public function mapColumnsAndDispatch(CommissionTariffUpload $upload, array $mapping): void
    {
        $upload->update([
            'column_map' => $mapping,
            'status' => 'processing',
        ]);

        CommissionTariffImportJob::dispatch($upload->id);
    }
}
