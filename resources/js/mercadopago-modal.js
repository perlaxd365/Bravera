let sdkPromise = null;
let cardForm = null;
let formElement = null;

const byId = (id) => document.getElementById(id);

const loadSdk = () => {
    if (window.MercadoPago) {
        return Promise.resolve(window.MercadoPago);
    }

    if (sdkPromise) {
        return sdkPromise;
    }

    sdkPromise = new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-brevare-mp-sdk]')
            ?? document.querySelector('script[src*="sdk.mercadopago.com/js/v2"]');

        const script = existing ?? document.createElement('script');

        script.addEventListener('load', () => {
            window.MercadoPago
                ? resolve(window.MercadoPago)
                : reject(new Error('El SDK de Mercado Pago no expuso el objeto global.'));
        }, { once: true });

        script.addEventListener('error', () => {
            reject(new Error('No pudimos cargar el SDK de Mercado Pago. Revisa tu conexión o el bloqueador de anuncios.'));
        }, { once: true });

        if (!existing) {
            script.src = 'https://sdk.mercadopago.com/js/v2';
            script.async = true;
            script.dataset.brevareMpSdk = 'true';
            document.head.appendChild(script);
        }

        window.setTimeout(() => {
            if (!window.MercadoPago) {
                reject(new Error('El SDK de Mercado Pago tardó demasiado en cargar.'));
            }
        }, 12000);
    });

    return sdkPromise;
};

const loadPublicKey = async () => {
    const response = await fetch('/pagos/mercadopago/configuracion', {
        cache: 'no-store',
        headers: { Accept: 'application/json' },
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok || !payload.publicKey) {
        throw new Error(payload.error || 'MP_PUBLIC_KEY no está configurada en el servidor.');
    }

    return payload.publicKey;
};

const setError = (message = '') => {
    const region = byId('mp-modal-error');

    if (!region) {
        return;
    }

    region.textContent = message;
    region.hidden = message === '';
};

const setResult = (message = '', isError = false) => {
    const region = byId('mp-modal-result');

    if (!region) {
        return;
    }

    region.textContent = message;
    region.hidden = message === '';
    region.classList.toggle('is-error', isError);
};

const setBusy = (isBusy) => {
    const button = byId('mp-modal-submit');

    if (!button) {
        return;
    }

    button.disabled = isBusy;
    button.textContent = isBusy
        ? 'Procesando...'
        : (button.dataset.defaultLabel || 'Pagar con Mercado Pago');
};

const secureFieldIds = [
    'mp-modal-cardNumber',
    'mp-modal-expirationDate',
    'mp-modal-securityCode',
];

const assertSecureFields = () => {
    const failures = [];

    secureFieldIds.forEach((id) => {
        const host = byId(id);
        const frames = host ? host.querySelectorAll('iframe') : [];
        const rect = host ? host.getBoundingClientRect() : { width: 0, height: 0 };
        const label = id === 'mp-modal-cardNumber'
            ? byId('mp-modal-card-label')
            : (id === 'mp-modal-expirationDate' ? byId('mp-modal-exp-label') : byId('mp-modal-cvv-label'));

        if (!host) failures.push(id + ': contenedor ausente');
        else if (frames.length !== 1) failures.push(id + ': iframe no montado');
        if (!label || label.getClientRects().length === 0) failures.push(id + ': etiqueta ausente');
        if (!rect.width || !rect.height) failures.push(id + ': contenedor sin dimensiones');
    });

    if (failures.length) {
        setError('No pudimos montar los campos seguros: ' + failures.join('; '));
        return false;
    }

    setError('');
    return true;
};

const mountForm = async () => {
    const modal = byId('mp-modal');
    const form = byId('mp-modal-form-checkout');

    if (!modal || !form) {
        return;
    }

    if (cardForm && formElement === form && document.contains(form)) {
        return;
    }

    cardForm = null;
    formElement = form;
    setError('');

    try {
        const MercadoPagoCtor = await loadSdk();
        const publicKey = await loadPublicKey();
        const mp = new MercadoPagoCtor(publicKey);

        cardForm = mp.cardForm({
            amount: modal.dataset.amount,
            iframe: true,
            form: {
                id: 'mp-modal-form-checkout',
                cardNumber: { id: 'mp-modal-cardNumber' },
                expirationDate: { id: 'mp-modal-expirationDate' },
                securityCode: { id: 'mp-modal-securityCode' },
                cardholderName: { id: 'mp-modal-cardholderName' },
                issuer: { id: 'mp-modal-issuer' },
                installments: { id: 'mp-modal-installments' },
                identificationType: { id: 'mp-modal-identificationType' },
                identificationNumber: { id: 'mp-modal-identificationNumber' },
            },
            callbacks: {
                onFormMounted: (error) => {
                    if (error) {
                        setError(String(error));
                        return;
                    }

                    requestAnimationFrame(assertSecureFields);
                },
                onSubmit: async (event) => {
                    event.preventDefault();

                    if (!cardForm) {
                        setError('El formulario de pago no está listo. Recarga la página.');
                        return;
                    }

                    const data = cardForm.getCardFormData();
                    const root = document.querySelector('[data-culqi-public-key]');
                    const component = window.Livewire?.find?.(root?.getAttribute('wire:id'));

                    if (!component) {
                        setError('No pudimos conectar el pago con el checkout. Recarga la página.');
                        return;
                    }

                    setBusy(true);
                    setResult('Procesando tu pago de forma segura...');

                    try {
                        await component.call(
                            'payMercadoPago',
                            data.token,
                            data.paymentMethodId,
                            data.identificationNumber ?? '',
                            data.issuerId ?? null,
                        );
                    } catch (error) {
                        console.error('[Mercado Pago] fallo al enviar el pago:', error);
                        setBusy(false);
                        setResult('No pudimos procesar el pago. Revisa tu conexión e inténtalo de nuevo.', true);
                    }
                },
            },
        });

        window.setTimeout(() => {
            if (!byId('mp-modal-expirationDate')?.querySelector('iframe')) {
                setError('El campo de vencimiento no se montó. Revisa tu conexión o el bloqueador de anuncios.');
            }
        }, 8000);
    } catch (error) {
        setError(error.message || 'No pudimos iniciar el formulario de Mercado Pago.');
    }
};

const openModal = () => {
    const modal = byId('mp-modal');

    if (!modal) {
        return;
    }

    document.body.style.overflow = 'hidden';
    document.body.dataset.mpModalOpen = '1';
    mountForm();
    modal.querySelector('.mp-modal__close')?.focus();
};

const waitForModal = (attempt = 0) => {
    if (byId('mp-modal')) {
        openModal();
        return;
    }

    if (attempt < 20) {
        window.setTimeout(() => waitForModal(attempt + 1), 50);
    }
};

const closeModal = () => {
    document.body.style.overflow = '';
    delete document.body.dataset.mpModalOpen;
};

const init = () => {
    if (!window.Livewire) {
        return;
    }

    window.Livewire.on('mercadopago:modal', () => {
        cardForm = null;
        formElement = null;
        requestAnimationFrame(() => waitForModal());
    });

    window.Livewire.on('mercadopago:modal-closed', closeModal);

    window.Livewire.on('mercadopago:result', (event) => {
        const payload = event?.detail ?? event ?? {};

        if (payload.status === 'paid' || payload.status === 'pending') {
            setBusy(false);
            window.location.assign(payload.url || '/mi-cuenta/pedidos');
            return;
        }

        setBusy(false);
        setResult(payload.message || 'No pudimos procesar el pago. Inténtalo de nuevo.', true);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !byId('mp-modal')) {
            return;
        }

        const root = document.querySelector('[data-culqi-public-key]');
        window.Livewire?.find?.(root?.getAttribute('wire:id'))?.call('closeMercadoPagoModal');
    });

    if (byId('mp-modal')) {
        openModal();
    }
};

if (window.Livewire) {
    init();
} else {
    document.addEventListener('livewire:init', init, { once: true });
}
