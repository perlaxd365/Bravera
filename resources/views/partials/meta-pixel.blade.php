@if (config('meta.pixel_enabled') && filled(config('meta.pixel_id')))
    <script>
        (() => {
            const config = window.BrevareMeta || {};
            const consent = () => window.BrevareConsent?.get?.() ?? null;
            let loaded = false;
            const sentEventIds = new Set();

            const load = () => {
                if (!config.pixelId || consent() !== 'all') {
                    return false;
                }

                if (loaded) {
                    return true;
                }

                !function(f,b,e,v,n,t,s){
                    if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                    n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];
                    s.parentNode.insertBefore(t,s)
                }(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');

                // Brevare envía cada evento explícitamente. Desactivamos la
                // detección automática de Meta para evitar AddToCart,
                // InitiateCheckout o AddPaymentInfo duplicados en formularios.
                fbq('set', 'autoConfig', false, config.pixelId);
                fbq('init', config.pixelId);
                loaded = true;

                return true;
            };

            const track = (name, data = {}, eventId = null) => {
                if (consent() !== 'all' || !load()) {
                    return;
                }

                if (eventId) {
                    if (sentEventIds.has(eventId)) {
                        return;
                    }

                    sentEventIds.add(eventId);
                    fbq('track', name, data, { eventID: eventId });
                } else {
                    fbq('track', name, data);
                }
            };

            const pageView = () => {
                if (consent() !== 'all' || !load()) {
                    return;
                }

                fbq('track', 'PageView');
            };

            window.BrevareMeta.track = track;
            window.BrevareMeta.pageView = pageView;

            if (consent() === 'all') {
                pageView();
            }

            document.addEventListener('livewire:navigated', pageView);

            window.addEventListener('brevare-consent-changed', (event) => {
                if (event.detail?.consent === 'all') {
                    pageView();
                }
            });

            let livewireEventsRegistered = false;

            const registerLivewireEvents = () => {
                if (livewireEventsRegistered || !window.Livewire) {
                    return;
                }

                livewireEventsRegistered = true;

                Livewire.on('brevare-meta-event', (event) => {
                    const detail = Array.isArray(event) ? event[0] : event;

                    if (!detail?.name) {
                        return;
                    }

                    track(detail.name, detail.data || {}, detail.event_id || null);
                });
            };

            if (window.Livewire) {
                registerLivewireEvents();
            } else {
                document.addEventListener('livewire:init', registerLivewireEvents);
            }
        })();
    </script>
@endif
