<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcodes</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #000;
        }

        /* Thermal Printer Layout */
        @if(in_array($paperSize, ['thermal_40_20', 'thermal_50_25']))
        body {
            width: 100%;
            height: 100%;
        }
        .label {
            width: 100%;
            height: 100%;
            text-align: center;
            page-break-after: always;
            box-sizing: border-box;
            padding: 2px;
            overflow: hidden;
        }
        .label:last-child {
            page-break-after: auto;
        }
        .product-name {
            font-size: 8px;
            font-weight: bold;
            line-height: 1.1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 2px;
        }
        .barcode-img {
            max-width: 100%;
            height: 20px;
            margin: 0 auto;
        }
        .sku-price {
            font-size: 7px;
            margin-top: 1px;
        }
        @endif

        /* A4 Sheet Layout (e.g. 40 labels per page) */
        @if($paperSize === 'a4')
        body {
            margin: 10mm;
        }
        .a4-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .a4-grid td {
            width: 25%; /* 4 columns */
            height: 25mm;
            text-align: center;
            border: 1px dashed #ccc;
            padding: 2mm;
            vertical-align: middle;
            box-sizing: border-box;
        }
        .product-name {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
        .barcode-img {
            max-width: 90%;
            height: 25px;
            margin: 0 auto;
        }
        .sku-price {
            font-size: 9px;
            margin-top: 2px;
        }
        @endif
    </style>
</head>
<body>

@if($paperSize === 'a4')
    <table class="a4-grid">
        <tr>
        @foreach($barcodes as $index => $barcode)
            <td>
                <div class="product-name">{{ Str::limit($barcode['name'], 20) }}</div>
                <div><img src="data:image/png;base64,{{ $barcode['image'] }}" class="barcode-img"></div>
                <div class="sku-price">{{ $barcode['sku'] }} | Rs. {{ number_format($barcode['price'], 2) }}</div>
            </td>
            @if(($index + 1) % 4 == 0)
                </tr>
                @if(($index + 1) % 40 == 0 && ($index + 1) < count($barcodes))
                    </table>
                    <div style="page-break-after: always;"></div>
                    <table class="a4-grid">
                    <tr>
                @else
                    <tr>
                @endif
            @endif
        @endforeach
        
        {{-- Close unclosed table row --}}
        @if(count($barcodes) % 4 != 0)
            @for($i = 0; $i < (4 - (count($barcodes) % 4)); $i++)
                <td></td>
            @endfor
            </tr>
        @endif
    </table>
@else
    {{-- Thermal Label Loop --}}
    @foreach($barcodes as $barcode)
        <div class="label">
            <div class="product-name">{{ Str::limit($barcode['name'], 25) }}</div>
            <img src="data:image/png;base64,{{ $barcode['image'] }}" class="barcode-img">
            <div class="sku-price">{{ $barcode['sku'] }} - Rs. {{ number_format($barcode['price'], 2) }}</div>
        </div>
    @endforeach
@endif

</body>
</html>
