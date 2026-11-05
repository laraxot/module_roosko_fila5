{{-- PWA minima della UI operatore: manifest, colore della barra, bundle scanner e service worker. --}}
<link rel="manifest" href="{{ $manifestUrl }}">
<meta name="theme-color" content="#0f766e">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
{!! $scannerScript !!}
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@js($serviceWorkerUrl), { scope: '/roosko/' }).catch(function () {});
        });
    }
</script>
