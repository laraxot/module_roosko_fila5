/*
 * Componente Alpine `roosko-scanner` (registrato come `rooskoScanner`): lettura QR/barcode con la
 * fotocamera del telefono tramite il pacchetto npm `qr-scanner` (bundle vite del modulo, niente CDN).
 *
 * Il codice letto va al server con `$wire.handleScan(code)`: la risoluzione (QR, URL `/l/{code}`,
 * codice stampato) e' la stessa dei lettori keyboard wedge.
 *
 * Ciclo di vita: la camera e lo scanner si rilasciano in `stop()`/`destroy()`; un `start()` ancora
 * in attesa quando il componente viene distrutto (navigazione Livewire) non lascia la camera accesa.
 */
import QrScanner from 'qr-scanner'

const MODAL_ID = 'roosko-camera'
const VIDEO_ID = 'roosko-camera-video'

function rooskoScanner() {
    return {
        scanner: null,
        error: null,
        isDestroyed: false,
        startToken: 0,

        async start() {
            this.error = null
            const token = ++this.startToken
            this.$dispatch('open-modal', { id: MODAL_ID })
            document.body.dataset.rooskoCamera = 'open'

            await this.$nextTick()
            const video = document.getElementById(VIDEO_ID)
            if (! video) {
                this.error = 'video'
                return
            }

            const scanner = new QrScanner(
                video,
                (result) => this.onDecode(result.data),
                {
                    preferredCamera: 'environment',
                    highlightScanRegion: true,
                    returnDetailedScanResult: true,
                    maxScansPerSecond: 8,
                },
            )

            try {
                await scanner.start()
            } catch (exception) {
                scanner.destroy()
                if (token === this.startToken && ! this.isDestroyed) {
                    this.error = exception?.message ?? String(exception)
                }
                return
            }

            // Componente distrutto o nuovo start() nel frattempo: questa sessione non e' piu' attuale.
            if (this.isDestroyed || token !== this.startToken) {
                scanner.stop()
                scanner.destroy()
                return
            }

            this.scanner = scanner
        },

        onDecode(code) {
            if (! code) {
                return
            }
            this.stop()
            this.$wire.handleScan(String(code))
        },

        stop() {
            this.startToken++
            if (this.scanner) {
                this.scanner.stop()
                this.scanner.destroy()
                this.scanner = null
            }
            delete document.body.dataset.rooskoCamera
            this.$dispatch('close-modal', { id: MODAL_ID })
        },

        destroy() {
            this.isDestroyed = true
            this.stop()
        },
    }
}

function register() {
    window.Alpine.data('rooskoScanner', rooskoScanner)
}

if (window.Alpine) {
    register()
} else {
    document.addEventListener('alpine:init', register, { once: true })
}
