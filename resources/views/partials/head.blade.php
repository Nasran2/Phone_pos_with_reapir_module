<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

<style>
    [x-cloak] { display: none !important; }
</style>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<!-- Global SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmAction(event, message) {
        event.preventDefault();
        let form = event.target.closest('form');
        let el = event.currentTarget; // The element that triggered the click
        if (el.tagName.toLowerCase() !== 'form' && !form) {
            // If it's a link or button without a form, handle redirect if href exists
            let href = el.getAttribute('href');
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#3b82f6',
                confirmButtonText: 'Yes, proceed!'
            }).then((result) => {
                if (result.isConfirmed && href) {
                    window.location.href = href;
                }
            });
            return false;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#3b82f6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
        return false;
    }
</script>
