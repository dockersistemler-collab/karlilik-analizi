<?php

namespace App\Jobs;

use App\Imports\CommissionTariffRowsImport;
use App\Models\CommissionTariffUpload;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class CommissionTariffImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $uploadId)
    {
    }

    public function handle(): void
    {
        $upload = CommissionTariffUpload::find($this->uploadId);
        if (!$upload) {
            return;
        }

        $upload->update(['status' => 'processing']);

        try {
            Excel::import(new CommissionTariffRowsImport($upload), $upload->stored_path);
            $upload->update(['status' => 'done']);
        } catch (Exception $e) {
            $upload->update([
                'status' => 'failed',
            ]);
            throw $e;
        }
    }
}
