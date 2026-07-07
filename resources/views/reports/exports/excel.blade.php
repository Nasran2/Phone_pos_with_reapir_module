<table>
    <thead>
        <tr>
            <th colspan="{{ count($headers) }}" style="font-size: 14px; font-weight: bold; text-align: center;">{{ $title }}</th>
        </tr>
        <tr>
            <th colspan="{{ count($headers) }}" style="text-align: center;">Generated at {{ $generated_at }} by {{ $generated_by }}</th>
        </tr>
        @if(count($filters) > 0)
        <tr>
            <th colspan="{{ count($headers) }}" style="text-align: center;">
                Filters: 
                @foreach($filters as $k => $v)
                    {{ ucfirst(str_replace('_', ' ', $k)) }}: {{ $v }}@if(!$loop->last), @endif
                @endforeach
            </th>
        </tr>
        @endif
        <tr>
            <th colspan="{{ count($headers) }}"></th>
        </tr>
        <tr>
            @foreach($headers as $header)
                <th style="font-weight: bold; background-color: #e2e8f0; border: 1px solid #000;">{{ $header }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($rows as $row)
            <tr>
                @foreach($row as $cell)
                    <td style="border: 1px solid #000;">{{ $cell }}</td>
                @endforeach
            </tr>
        @endforeach
        
        @if(count($totals) > 0)
            <tr>
                @foreach($headers as $key => $header)
                    <td style="font-weight: bold; background-color: #f1f5f9; border: 1px solid #000;">{{ $totals[$key] ?? '' }}</td>
                @endforeach
            </tr>
        @endif
    </tbody>
</table>
