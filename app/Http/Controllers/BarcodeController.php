<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class BarcodeController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('is_active', true)->get();
        return view('barcodes.index', compact('products'));
    }

    public function print(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'paper_size' => 'required|string',
        ]);

        $items = $request->input('items');
        $paperSize = $request->input('paper_size'); // e.g. 'a4', 'thermal_40_20', 'thermal_50_25'

        $barcodes = [];
        $generator = new BarcodeGeneratorPNG();

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) continue;

            $barcodeData = $product->sku ?? $product->barcode ?? str_pad($product->id, 8, '0', STR_PAD_LEFT);
            $barcodeImage = base64_encode($generator->getBarcode($barcodeData, $generator::TYPE_CODE_128));

            for ($i = 0; $i < $item['quantity']; $i++) {
                $barcodes[] = [
                    'name' => $product->name,
                    'sku' => $barcodeData,
                    'price' => $product->retail_price ?? $product->price ?? 0,
                    'image' => $barcodeImage,
                ];
            }
        }

        if (empty($barcodes)) {
            return back()->with('error', 'No valid products selected to print.');
        }

        $pdf = Pdf::loadView('barcodes.print', compact('barcodes', 'paperSize'));

        if ($paperSize === 'a4') {
            $pdf->setPaper('a4', 'portrait');
        } elseif ($paperSize === 'thermal_40_20') {
            // 40mm x 20mm = ~113.38pt x ~56.69pt
            $pdf->setPaper([0, 0, 113.38, 56.69], 'landscape');
        } elseif ($paperSize === 'thermal_50_25') {
            // 50mm x 25mm = ~141.73pt x ~70.86pt
            $pdf->setPaper([0, 0, 141.73, 70.86], 'landscape');
        } else {
            $pdf->setPaper('a4', 'portrait');
        }

        return $pdf->stream('barcodes.pdf');
    }
}
