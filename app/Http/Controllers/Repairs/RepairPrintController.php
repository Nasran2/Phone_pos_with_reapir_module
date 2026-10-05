<?php

namespace App\Http\Controllers\Repairs;

use App\Http\Controllers\Controller;
use App\Models\RepairJob;
use App\Services\Repairs\RepairActivityService;
use App\Support\SystemSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Picqer\Barcode\BarcodeGeneratorSVG;

class RepairPrintController extends Controller
{
    public function card(Request $request, RepairJob $repairJob): Response
    {
        return $this->render($request, $repairJob, 'card');
    }

    public function invoice(Request $request, RepairJob $repairJob): Response
    {
        return $this->render($request, $repairJob, 'invoice');
    }

    private function render(Request $request, RepairJob $job, string $kind): Response
    {
        $request->validate(['paper' => ['nullable', 'in:A4,80mm'], 'pdf' => ['nullable', 'boolean']]);
        $job->load(['customer', 'device', 'store', 'repairStatus', 'technician', 'parts', 'charges', 'payments.cheque', 'warranty', 'checklist', 'notes' => fn ($q) => $q->where('type', 'Customer')]);
        app(RepairActivityService::class)->log($job, $kind === 'card' ? 'Job card printed' : 'Invoice printed');
        $barcode = (new BarcodeGeneratorSVG)->getBarcode($job->barcode, BarcodeGeneratorSVG::TYPE_CODE_128, 2, 42);
        $logoSource = null;
        $logo = SystemSettings::get('business_logo', null, 'business_settings');
        if ($logo && SystemSettings::bool('show_logo', true, 'invoice_settings') && Storage::disk('public')->exists($logo)) {
            $mime = Storage::disk('public')->mimeType($logo);
            if (in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
                $logoSource = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logo));
            }
        }
        $data = compact('job', 'kind', 'barcode', 'logoSource') + ['paper' => $request->input('paper', 'A4')];
        if ($request->boolean('pdf')) {
            $pdf = Pdf::loadView('repairs.print', $data);
            if ($data['paper'] === '80mm') {
                $pdf->setPaper([0, 0, 226.77, 1000]);
            }

            return $pdf->stream($job->job_no.'-'.$kind.'.pdf');
        }

        return response()->view('repairs.print', $data)->header('Cache-Control', 'private, no-store');
    }
}
