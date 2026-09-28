import Swal from 'sweetalert2';

export const plain = (value) => {
    if (Array.isArray(value) && value.length === 2
        && value[1] && typeof value[1] === 'object' && 's' in value[1]) {
        return plain(value[0]);
    }

    if (value && typeof value === 'object' && !Array.isArray(value)) {
        return Object.fromEntries(
            Object.entries(value).map(([key, item]) => [key, plain(item)])
        );
    }

    return value;
};

export const notify = (message, type = 'error') => {
    Livewire.dispatch('notify', { type, message });
};

export const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({
    '&': '&',
    '<': '<',
    '>': '>',
    '"': '"',
    "'": "'",
})[char]);

export const loadCulqi = () => new Promise((resolve, reject) => {
    if (window.CulqiCheckout) {
        resolve(window.CulqiCheckout);
        return;
    }

    const inyectar = () => {
        const script = document.createElement('script');
        script.src = 'https://js.culqi.com/checkout-js';
        script.async = true;
        script.dataset.brevareCulqi = 'true';
        document.head.appendChild(script);
        return script;
    };

    const esperar = (script) => {
        script.addEventListener('load', () => {
            script.dataset.cargado = '1';
            window.CulqiCheckout
                ? resolve(window.CulqiCheckout)
                : reject(new Error('culqi_sin_global'));
        }, { once: true });

        script.addEventListener('error', () => {
            script.dataset.cargado = 'error';
            reject(new Error('culqi_script'));
        }, { once: true });
    };

    const pendiente = Array.from(document.querySelectorAll('script[data-brevare-culqi]'))
        .find((script) => script.dataset.cargado !== '1');

    esperar(pendiente ?? inyectar());

    window.setTimeout(() => {
        if (!window.CulqiCheckout) {
            reject(new Error('culqi_timeout'));
        }
    }, 15000);
});

export const safeErrorMessage = (error) => {
    console.warn('[Culqi] el checkout devolvió un error', {
        type: error?.type ?? null,
        code: error?.code ?? null,
        param: error?.param ?? null,
        user_message: error?.user_message ?? null,
        merchant_message: error?.merchant_message ?? null,
    });

    const text = (error?.user_message ?? '').toLowerCase();
    const sendsToSupport = text.includes('soporte') || text.includes('culqi.com');

    if (error?.param === 'card_number' && !error?.code) {
        return 'No pudimos validar esa tarjeta. Revisa el número e inténtalo de nuevo.';
    }

    if (sendsToSupport) {
        return 'Culqi no pudo procesar esa tarjeta. Prueba con otra tarjeta o inténtalo más tarde.';
    }

    if (error?.code === 'card_declined') {
        return 'Tu banco rechazó la operación. Inténtalo con otra tarjeta.';
    }

    if (error?.type === 'parameter_error') {
        return 'Revisa los datos de tu tarjeta e inténtalo de nuevo.';
    }

    return 'No pudimos procesar el pago. Inténtalo de nuevo.';
};

let swalInstance = null;

const closeSweetAlert = () => {
    if (swalInstance) {
        swalInstance.close();
        swalInstance = null;
    }
};

export const openModal = async (rawSession, publicKey, state) => {
    const session = plain(rawSession);

    if (!session) {
        console.error('[Culqi] openModal: sesión vacía');
        return;
    }

    console.debug('[Culqi] openModal llamado con:', session);

    const methods = session.methods && typeof session.methods === 'object' && !Array.isArray(session.methods)
        ? session.methods
        : null;

    if (methods === null) {
        console.error('[Culqi] los métodos de pago no llegaron como objeto.', session);
        notify('No pudimos abrir el formulario de Culqi. Revisa tu conexión e inténtalo de nuevo.');
        return;
    }

    let Ctor;
    try {
        Ctor = await loadCulqi();
    } catch (e) {
        console.error('[Culqi] Error cargando Culqi:', e);
        notify('No pudimos cargar el formulario seguro de Culqi. Revisa tu conexión e inténtalo de nuevo.');
        return;
    }

    console.debug('[Culqi] CulqiCheckout cargado, creando instancia...');

    const checkout = new Ctor(publicKey, {
        settings: {
            title: `Pago del pedido ${session.orderNumber}`,
            currency: session.currency,
            amount: session.amount,
            order: session.gatewayOrderId,
        },
        client: { email: session.email },
        options: {
            lang: 'es',
            modal: true,
            installments: false,
            paymentMethods: methods,
        },
        appearance: {
            theme: 'default',
        },
    });

    checkout.culqi = async () => {
        console.log('========== CULQI DEBUG YAPE ==========');
        console.log('TOKEN:', checkout.token);
        console.log('ORDER:', checkout.order);
        console.log('METHOD:', checkout.methodValue);
        console.log('ERROR:', checkout.error);
        console.log('TOKEN ID:', checkout.token?.id);
        console.log('CHECKOUT COMPLETO:', checkout);
        console.log('======================================');

        const root = document.querySelector('[data-culqi-public-key]');
        const currentWireId = root?.getAttribute('wire:id') ?? state.wireId;

        // =====================================================
        // PAGO CON TARJETA
        // SOLO UN TOKEN VÁLIDO PUEDE INICIAR EL COBRO
        // =====================================================
        if (checkout.token?.id) {
            // =====================================================
            // PAGO CON TARJETA
            // =====================================================
            const tokenId = checkout.token?.id ?? '';

            if (tokenId.startsWith('tkn_')) {

                console.log('[Culqi] Token de tarjeta recibido:', tokenId);

                const component = window.Livewire?.find?.(currentWireId);

                if (!component) {
                    console.error(
                        '[Culqi] No se encontró el componente Livewire:',
                        currentWireId
                    );

                    notify(
                        'No pudimos conectar el pago con el checkout. Recarga la página e inténtalo nuevamente.'
                    );

                    return;
                }

                showPaymentProcessing();

                if (typeof checkout.close === 'function') {
                    checkout.close();
                }

                try {

                    await component.call('completeCulqiCard', {
                        token: tokenId
                    });

                    console.debug(
                        '[Culqi] Token de tarjeta enviado correctamente.'
                    );

                } catch (error) {

                    console.error(
                        '[Culqi] Error llamando completeCulqiCard:',
                        error
                    );

                    hidePaymentProcessing();

                    notify(
                        'Ocurrió un error al procesar el pago. Inténtalo nuevamente.'
                    );
                }

                return;
            }
            // =====================================================
            // PAGO CON YAPE
            // =====================================================
            if (tokenId.startsWith('ype_')) {

                console.log('[Culqi] Token de Yape recibido:', tokenId);

                const component = window.Livewire?.find?.(currentWireId);

                if (!component) {
                    console.error(
                        '[Culqi] No se encontró el componente Livewire:',
                        currentWireId
                    );

                    notify(
                        'No pudimos conectar el pago con el checkout. Recarga la página e inténtalo nuevamente.'
                    );

                    return;
                }

                showPaymentProcessing();

                if (typeof checkout.close === 'function') {
                    checkout.close();
                }

                try {

                    await component.call(
                        'completeCulqiYape',
                        tokenId
                    );

                    console.debug(
                        '[Culqi] Token de Yape enviado correctamente.'
                    );

                } catch (error) {

                    console.error(
                        '[Culqi] Error llamando completeCulqiAsync para Yape:',
                        error
                    );

                    hidePaymentProcessing();

                    notify(
                        'Ocurrió un error al procesar el pago con Yape. Inténtalo nuevamente.'
                    );
                }

                return;
            }

            // =====================================================
            // PAGO MEDIANTE ORDEN
            // YAPE / BILLETERA / BANCA MÓVIL / AGENTE / CUOTÉALO
            // =====================================================
            if (checkout.order) {

                console.debug(
                    '[Culqi] Orden recibida:',
                    checkout.order
                );

                console.debug(
                    '[Culqi] Método recibido:',
                    checkout.methodValue
                );

                const component = window.Livewire?.find?.(currentWireId);

                if (!component) {
                    console.error(
                        '[Culqi] No se encontró el componente Livewire:',
                        currentWireId
                    );

                    notify(
                        'No pudimos conectar el pago con el checkout. Recarga la página e inténtalo nuevamente.'
                    );

                    return;
                }

                showPaymentProcessing();

                try {

                    await component.call(
                        'completeCulqiAsync',
                        checkout.methodValue ?? ''
                    );

                    console.debug(
                        '[Culqi] Método asíncrono enviado a completeCulqiAsync.'
                    );

                } catch (error) {

                    console.error(
                        '[Culqi] Error llamando completeCulqiAsync:',
                        error
                    );

                    hidePaymentProcessing();

                    notify(
                        'Ocurrió un error al procesar el pago. Inténtalo nuevamente.'
                    );
                }

                return;
            }


            // =====================================================
            // ERROR DE CULQI
            // =====================================================
            if (checkout.error) {
                notify(safeErrorMessage(checkout.error));
                return;
            }


            // =====================================================
            // CIERRE / CANCELACIÓN
            // =====================================================
            console.debug(
                '[Culqi] Checkout cerrado/cancelado sin token.'
            );
        }

        // =====================================================
        // ERROR DE CULQI
        // =====================================================
        if (checkout.error) {
            notify(safeErrorMessage(checkout.error));
            return;
        }

        // =====================================================
        // CIERRE / CANCELACIÓN
        //
        // IMPORTANTE:
        // NO hacer nada aquí.
        // NO llamar a Laravel.
        // NO registrar pago.
        // NO usar checkout.order como confirmación.
        // =====================================================

        console.debug('[Culqi] Checkout cerrado/cancelado sin token.');
    };

    // Helper para obtener el wireId actual (puede cambiar tras re-renders de Livewire)
    const getCurrentWireId = () => {
        const root = document.querySelector('[data-culqi-public-key]');
        return root?.getAttribute('wire:id') ?? state.wireId;
    };





    state.checkout = checkout;

    // El SDK maneja su propio modal, solo llamamos open()
    console.debug('[Culqi] Abriendo modal de Culqi...');
    checkout.open();

    // Verificar si se abrió correctamente
    setTimeout(() => {
        if (checkout.isOpen === false) {
            console.error('[Culqi] el modal no llegó a montarse', { publicKey, session });
            notify('No pudimos abrir el formulario de Culqi. Revisa tu conexión e inténtalo de nuevo.');
            return;
        }
        console.debug('[Culqi] Checkout abierto correctamente');
    }, 1000);
};

export const handleCulqiSettled = (payload) => {
    console.debug('[Culqi] >>> handleCulqiSettled LLAMADO <<<', payload);
    console.debug('[Culqi] Payload type:', typeof payload, 'keys:', Object.keys(payload));
    const kind = payload.kind ?? 'idle';

    if (kind === 'idle') {
        console.debug('[Culqi] kind=idle, saliendo');
        return;
    }

    if (kind === 'paid') {
        console.debug('[Culqi] kind=paid, URL:', payload.url);
        alert('¡Pago confirmado!');
        if (payload.url) {
            console.debug('[Culqi] Redirigiendo a:', payload.url);
            window.location.href = payload.url;
        } else {
            console.error('[Culqi] kind=paid pero no hay URL en el payload');
        }
    } else if (kind === 'pending') {
        alert('Estamos verificando tu pago. Te avisamos en cuanto Culqi confirme la operación.');
    }
};

// Registrar listener global para culqi:settled inmediatamente (no depende de initCheckout)
if (typeof Livewire !== 'undefined') {
    Livewire.on('culqi:settled', (event) => {
        console.debug('[Culqi] >>> EVENTO culqi:settled RECIBIDO (global) <<<', event);
        console.debug('[Culqi] Event detail:', event?.detail);
        const payload = event?.detail ?? event ?? {};
        handleCulqiSettled(payload);
    });
} else {
    // Si Livewire no está listo, esperar a que lo esté
    document.addEventListener('livewire:init', () => {
        Livewire.on('culqi:settled', (event) => {
            console.debug('[Culqi] >>> EVENTO culqi:settled RECIBIDO (global, deferred) <<<', event);
            const payload = event?.detail ?? event ?? {};
            handleCulqiSettled(payload);
        });
    });
}

// Fallback: también escuchar en window por si Livewire.on no funciona
window.addEventListener('culqi:settled', (event) => {
    console.debug('[Culqi] >>> EVENTO culqi:settled EN WINDOW <<<', event);
    console.debug('[Culqi] Window event detail:', event?.detail);
    const payload = event?.detail ?? event ?? {};
    handleCulqiSettled(payload);
});

export const initCheckout = (publicKey, wireId) => {
    const state = (window.__brevareCulqi ??= {});
    state.wireId = wireId;

    console.debug('[Culqi] initCheckout llamado, registrando listeners...', { wireId });

    Livewire.on('culqi:open', (event) => {
        console.debug('[Culqi] >>> EVENTO culqi:open RECIBIDO <<<', event);
        const session = plain(event?.session ?? event?.detail?.session ?? null);

        if (!session) {
            console.error('[Culqi] el evento culqi:open llegó sin sesión de pago.');
            notify('No pudimos abrir el pago. Vuelve a intentarlo.');
            return;
        }

        console.debug('[Culqi] Abriendo modal con sesión:', session);
        openModal(session, publicKey, state);
    });

    Livewire.hook('request', ({ fail }) => {
        if (typeof fail === 'function') {
            fail(() => {
                if (state.checkout) {
                    state.checkout.close();
                    state.checkout = null;
                }
            });
        }
    });
};

const autoInit = () => {
    const root = document.querySelector('[data-culqi-public-key]');

    if (!root) {
        // Culqi solo se necesita en la vista Checkout.
        // En las demás vistas simplemente no hacemos nada.
        return;
    }

    console.debug('[Culqi] Elemento root encontrado:', root);
    console.debug('[Culqi] publicKey:', root.dataset.culqiPublicKey);

    const publicKey = root.dataset.culqiPublicKey;
    if (!publicKey) return;

    const tryInit = () => {
        if (window.Livewire) {
            const wireId = root.getAttribute('wire:id');
            console.debug('[Culqi] Livewire disponible, inicializando...', { wireId });
            if (wireId) {
                initCheckout(publicKey, wireId);
                return true;
            }
        } else {
            console.warn('[Culqi] window.Livewire no está disponible aún');
        }
        return false;
    };

    // Try immediately
    if (tryInit()) return;

    // Fallback: wait for livewire:init
    document.addEventListener('livewire:init', () => {
        console.debug('[Culqi] livewire:init disparado, intentando de nuevo...');
        tryInit();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoInit);
} else {
    autoInit();
}

const showPaymentProcessing = () => {
    const overlay = document.getElementById('culqi-processing-overlay');

    if (!overlay) {
        console.warn('[Culqi] No se encontró #culqi-processing-overlay');
        return;
    }

    overlay.style.display = 'flex';

    // Bloquear scroll
    document.body.style.overflow = 'hidden';

    console.debug('[Culqi] Página bloqueada - procesando pago...');
};

const hidePaymentProcessing = () => {
    const overlay = document.getElementById('culqi-processing-overlay');

    if (overlay) {
        overlay.style.display = 'none';
    }

    document.body.style.overflow = '';

    console.debug('[Culqi] Overlay de procesamiento ocultado.');
};



export default {};

