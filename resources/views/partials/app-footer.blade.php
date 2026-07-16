@php
    $developerName = config('app.developer.name', 'twinsofte.com');
    $developerPhone = config('app.developer.phone', '0762472822');
    $developerWeb = config('app.developer.web', 'twinsofte.com');
    $developerUrl = $developerWeb;

    if ($developerUrl && !str_starts_with($developerUrl, 'http://') && !str_starts_with($developerUrl, 'https://')) {
        $developerUrl = 'https://' . $developerUrl;
    }
@endphp

<footer class="border-t border-slate-200 bg-white px-6 py-4 text-[11px] font-semibold text-slate-500">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <span>&copy; {{ now()->year }} {{ \App\Support\SystemSettings::get('system_name', config('app.name', 'Phone Shop POS'), 'business_settings') }}. All rights reserved.</span>
        <span>
            Powered by
            @if($developerUrl)
                <a href="{{ $developerUrl }}" target="_blank" rel="noopener" class="font-extrabold text-blue-600 hover:text-blue-700 hover:underline">{{ $developerName }}</a>
            @else
                <span class="font-extrabold text-slate-700">{{ $developerName }}</span>
            @endif
            @if($developerPhone)
                <span class="text-slate-300">|</span> {{ $developerPhone }}
            @endif
        </span>
    </div>
</footer>

<script>
    window.appCurrencySymbol = @json(\App\Support\SystemSettings::currencySymbol());

    document.addEventListener('DOMContentLoaded', () => {
        const currency = window.appCurrencySymbol || 'Rs.';
        if (currency === 'Rs.') {
            return;
        }

        const skipTags = new Set(['SCRIPT', 'STYLE', 'TEXTAREA']);
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, {
            acceptNode(node) {
                if (!node.nodeValue.includes('Rs.')) {
                    return NodeFilter.FILTER_REJECT;
                }

                if (node.parentElement && skipTags.has(node.parentElement.tagName)) {
                    return NodeFilter.FILTER_REJECT;
                }

                return NodeFilter.FILTER_ACCEPT;
            }
        });

        const nodes = [];
        while (walker.nextNode()) {
            nodes.push(walker.currentNode);
        }

        nodes.forEach((node) => {
            node.nodeValue = node.nodeValue.replaceAll('Rs.', currency);
        });
    });
</script>
