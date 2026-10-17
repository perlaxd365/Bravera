let sdkPromise = null;
let brickController = null;
let brickContainer = null;
let mountingBrick = false;
let mountQueued = false;
let readyTimeout = null;
let brickLayoutObserver = null;
let brickDomObserver = null;

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

const setLoading = (isLoading) => {
    const loading = byId('mp-modal-brick-loading');

    if (loading) {
        loading.hidden = !isLoading;
    }
};

const NEUTRAL_CARD_MARKUP = '<svg viewBox="0 0 40 26" aria-hidden="true"><defs><clipPath id="mp-card-clip"><rect width="40" height="26" rx="4"/></clipPath></defs><g clip-path="url(#mp-card-clip)"><rect width="40" height="26" fill="#ffffff"/><rect y="5" width="40" height="4.2" fill="#ece8e1"/><rect x="6" y="15.5" width="9" height="3" rx="1.5" fill="#d7d2ca"/></g><rect x="0.5" y="0.5" width="39" height="25" rx="3.5" fill="none" stroke="#ded8ce"/></svg>';

const BRAND_MARKUP = {
    visa: '<svg viewBox="0 0 40 26" aria-hidden="true"><rect width="40" height="26" rx="4" fill="#ffffff"/><rect x="0.5" y="0.5" width="39" height="25" rx="3.5" fill="none" stroke="#e3ddd2"/><text x="20" y="17.4" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="10.5" font-weight="800" font-style="italic" letter-spacing="0.3" fill="#1a1f71">VISA</text></svg>',
    mastercard: '<svg viewBox="0 0 40 26" aria-hidden="true"><rect width="40" height="26" rx="4" fill="#ffffff"/><rect x="0.5" y="0.5" width="39" height="25" rx="3.5" fill="none" stroke="#e3ddd2"/><circle cx="16" cy="13" r="7.4" fill="#eb001b"/><circle cx="24" cy="13" r="7.4" fill="#f79e1b"/><path d="M20 6.6a7.4 7.4 0 0 0 0 12.8 7.4 7.4 0 0 0 0-12.8Z" fill="#ff5f00"/></svg>',
    amex: '<svg viewBox="0 0 40 26" aria-hidden="true"><rect width="40" height="26" rx="4" fill="#006fcf"/><text x="20" y="16.6" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="8.5" font-weight="800" letter-spacing="0.2" fill="#ffffff">AMEX</text></svg>',
    diners: '<svg viewBox="0 0 40 26" aria-hidden="true"><rect width="40" height="26" rx="4" fill="#ffffff"/><rect x="0.5" y="0.5" width="39" height="25" rx="3.5" fill="none" stroke="#e3ddd2"/><circle cx="20" cy="13" r="8" fill="#0079be"/><circle cx="20" cy="13" r="4.8" fill="#ffffff"/></svg>',
    discover: '<svg viewBox="0 0 40 26" aria-hidden="true"><rect width="40" height="26" rx="4" fill="#ffffff"/><rect x="0.5" y="0.5" width="39" height="25" rx="3.5" fill="none" stroke="#e3ddd2"/><text x="15" y="16.4" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="7" font-weight="800" fill="#231f20">DISCOVER</text><circle cx="33" cy="17" r="4.4" fill="#f48120"/></svg>',
};

let currentBrand = null;

const brandFromBin = (bin) => {
    const digits = String(bin ?? '').replace(/\D+/g, '');

    if (digits.length < 2) {
        return null;
    }

    if (/^3[47]/.test(digits)) {
        return 'amex';
    }

    if (digits.length < 4) {
        return null;
    }

    if (/^4/.test(digits)) {
        return 'visa';
    }

    if (/^(5[0-5]|2[2-7])/.test(digits)) {
        return 'mastercard';
    }

    if (/^(30[0-5]|3[689])/.test(digits)) {
        return 'diners';
    }

    if (/^(6011|65|64[4-9])/.test(digits)) {
        return 'discover';
    }

    return null;
};

const resetBrandBadge = () => {
    const badge = byId('mp-modal-brand');

    currentBrand = 'neutral';

    if (!badge) {
        return;
    }

    badge.innerHTML = NEUTRAL_CARD_MARKUP;
    badge.classList.remove('is-detected');
    badge.hidden = false;
};

const positionBrandBadge = () => {
    const badge = byId('mp-modal-brand');
    const container = byId('mp-modal-card-brick');
    const field = container?.closest('.mp-modal__card-field');

    if (!badge || !container || !field) {
        return;
    }

    const frame = container.querySelector('iframe[name="cardNumber"]');
    const box = frame?.parentElement;

    if (!frame || !box) {
        return;
    }

    const fieldRect = field.getBoundingClientRect();
    const boxRect = box.getBoundingClientRect();

    if (!boxRect.height) {
        return;
    }

    // Deja el hueco de la tarjeta dentro del propio campo, delante del número.
    if (box.style.paddingLeft !== '58px') {
        box.style.paddingLeft = '58px';
    }

    if (!badge.hidden) {
        badge.style.top = `${Math.round(boxRect.top - fieldRect.top + (boxRect.height - 32) / 2)}px`;
        badge.style.left = `${Math.round(boxRect.left - fieldRect.left + 8)}px`;
    }
};

const setBrandBadge = (brand) => {
    const badge = byId('mp-modal-brand');

    if (!badge) {
        return;
    }

    if (!brand || !BRAND_MARKUP[brand]) {
        if (currentBrand !== 'neutral') {
            resetBrandBadge();
            requestAnimationFrame(positionBrandBadge);
        }

        return;
    }

    if (currentBrand !== brand) {
        badge.innerHTML = BRAND_MARKUP[brand];
        currentBrand = brand;
        badge.classList.add('is-detected');
    }

    badge.hidden = false;
    requestAnimationFrame(positionBrandBadge);
};

const unmountBrick = async () => {
    const controller = brickController;

    brickController = null;
    brickContainer = null;
    currentBrand = null;

    if (brickLayoutObserver) {
        brickLayoutObserver.disconnect();
        brickLayoutObserver = null;
    }

    if (brickDomObserver) {
        brickDomObserver.disconnect();
        brickDomObserver = null;
    }

    if (readyTimeout) {
        window.clearTimeout(readyTimeout);
        readyTimeout = null;
    }

    if (!controller) {
        return;
    }

    try {
        await controller.unmount();
    } catch (error) {
        console.warn('[Mercado Pago] no se pudo desmontar el formulario:', error);
    }
};

/**
 * El Brick de Mercado Pago ofrece DNI, C.E, RUC y "Otro". En Brevare solo se
 * acepta DNI, así que se elimina "Otro" del selector nativo del Brick.
 */
const restrictIdentificationTypes = () => {
    const container = byId('mp-modal-card-brick');

    if (!container) {
        return;
    }

    container.querySelectorAll('select').forEach((select) => {
        let removed = false;

        Array.from(select.options).forEach((option) => {
            if (/^\s*otro\s*$/i.test(option.textContent || '')) {
                option.remove();
                removed = true;
            }
        });

        if (removed && !select.value) {
            const dni = Array.from(select.options).find((option) => /^\s*dni\b/i.test(option.textContent || ''));

            if (dni) {
                select.value = dni.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    });
};

/**
 * El Brick inserta su título y sus banderas después de montar el iframe, así
 * que la tarjeta debe recalcular su posición cuando cambia la altura.
 */
const observeBrickLayout = (container) => {
    if (!('ResizeObserver' in window)) {
        return;
    }

    brickLayoutObserver?.disconnect();
    brickLayoutObserver = new ResizeObserver(() => positionBrandBadge());
    brickLayoutObserver.observe(container);

    const inputBox = container.querySelector('iframe[name="cardNumber"]')?.parentElement;

    if (inputBox) {
        brickLayoutObserver.observe(inputBox);
    }
};

const submitBrick = (formData) => new Promise((resolve) => {
    const root = document.querySelector('[data-culqi-public-key]');
    const component = window.Livewire?.find?.(root?.getAttribute('wire:id'));

    if (!component) {
        setError('No pudimos conectar el pago con el checkout. Recarga la página.');
        resolve();
        return;
    }

    setError('');
    setResult('Procesando tu pago de forma segura…');

    component.call('payMercadoPagoBrick', formData)
        .catch((error) => {
            console.error('[Mercado Pago] fallo al enviar el pago:', error);
            setResult('No pudimos procesar el pago. Revisa tu conexión e inténtalo de nuevo.', true);
        })
        .finally(() => resolve());
});

const mountBrick = async () => {
    const modal = byId('mp-modal');
    const container = byId('mp-modal-card-brick');

    if (!modal || !container) {
        return;
    }

    if (brickController && brickContainer === container && document.contains(container)) {
        return;
    }

    if (mountingBrick) {
        mountQueued = true;
        return;
    }

    mountingBrick = true;
    setLoading(true);

    const brandBadge = byId('mp-modal-brand');
    if (brandBadge) {
        brandBadge.hidden = true;
    }

    try {
        await unmountBrick();
        container.replaceChildren();

        const MercadoPagoCtor = await loadSdk();
        const publicKey = await loadPublicKey();
        const mp = new MercadoPagoCtor(publicKey);
        const bricks = mp.bricks();

        const initialization = {
            amount: Number(modal.dataset.amount || 0),
        };
        const payerEmail = (modal.dataset.payerEmail || '').trim();

        if (payerEmail) {
            initialization.payer = { email: payerEmail };
        }

        brickContainer = container;
        brickController = await bricks.create('cardPayment', 'mp-modal-card-brick', {
            initialization,
            customization: {
                visual: {
                        hideFormTitle: true,
                    style: {
                        theme: 'flat',
                        customVariables: {
                            baseColor: '#0a0a0c',
                            buttonTextColor: '#ffffff',
                            textPrimaryColor: '#0a0a0c',
                            textSecondaryColor: '#70707a',
                            inputBackgroundColor: '#ffffff',
                            formBackgroundColor: 'transparent',
                            outlinePrimaryColor: 'rgba(60, 60, 67, 0.18)',
                            inputFocusedBoxShadow: '0 0 0 4px rgba(10, 132, 255, 0.16)',
                            borderRadiusMedium: '14px',
                            borderRadiusLarge: '18px',
                            fontSizeMedium: '14px',
                            fontSizeLarge: '15px',
                            inputVerticalPadding: '13px',
                            inputHorizontalPadding: '14px',
                            formPadding: '0px',
                        },
                    },
                    texts: {
                        formTitle: 'Datos de la tarjeta',
                        cardNumber: { label: 'Número de tarjeta' },
                        expirationDate: { label: 'Vencimiento' },
                        securityCode: { label: 'Código de seguridad' },
                        cardholderName: { label: 'Nombre del titular' },
                        cardholderIdentification: { label: 'DNI del titular' },
                        installmentsSectionTitle: 'Cuotas',
                        selectInstallments: 'Número de cuotas',
                        selectIssuerBank: 'Banco emisor',
                        formSubmit: `Pagar S/ ${Number(modal.dataset.amount || 0).toFixed(2)}`,
                    },
                },
                paymentMethods: {
                    minInstallments: 1,
                    maxInstallments: 1,
                },
            },
            callbacks: {
                onReady: () => {
                    if (readyTimeout) {
                        window.clearTimeout(readyTimeout);
                        readyTimeout = null;
                    }

                    setLoading(false);
                    resetBrandBadge();
                    restrictIdentificationTypes();

                    brickDomObserver?.disconnect();
                    brickDomObserver = new MutationObserver(restrictIdentificationTypes);
                    brickDomObserver.observe(container, { childList: true, subtree: true });

                    window.setTimeout(positionBrandBadge, 120);
                    window.setTimeout(positionBrandBadge, 500);
                },
                onError: (error) => {
                    setLoading(false);
                    console.error('[Mercado Pago] error del formulario:', error);
                    setError('No pudimos mostrar el formulario de tarjeta. Revisa tu conexión e inténtalo otra vez.');
                },
                onBinChange: (bin) => setBrandBadge(brandFromBin(bin)),
                onSubmit: submitBrick,
            },
        });

        observeBrickLayout(container);

        readyTimeout = window.setTimeout(() => {
            const loading = byId('mp-modal-brick-loading');

            if (loading && !loading.hidden) {
                setLoading(false);
                setError('El formulario de pago tardó demasiado en cargar. Cierra y vuelve a intentarlo.');
            }
        }, 15000);
    } catch (error) {
        setLoading(false);
        setError(error.message || 'No pudimos iniciar el formulario de Mercado Pago.');
    } finally {
        mountingBrick = false;

        if (mountQueued) {
            mountQueued = false;
            window.setTimeout(() => mountBrick(), 80);
        }
    }
};

const remountBrick = () => {
    void (async () => {
        await unmountBrick();

        const container = byId('mp-modal-card-brick');

        if (container) {
            container.replaceChildren();
        }

        await new Promise((resolve) => window.setTimeout(resolve, 80));
        await mountBrick();
    })();
};

const openModal = () => {
    const modal = byId('mp-modal');

    if (!modal) {
        return;
    }

    document.body.style.overflow = 'hidden';
    document.body.dataset.mpModalOpen = '1';
    setError('');
    setResult('');
    mountBrick();
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
    void unmountBrick();
};

const init = () => {
    if (!window.Livewire) {
        return;
    }

    window.Livewire.on('mercadopago:modal', () => {
        requestAnimationFrame(() => waitForModal());
    });

    window.Livewire.on('mercadopago:modal-closed', closeModal);

    window.Livewire.on('mercadopago:result', (event) => {
        const payload = event?.detail ?? event ?? {};

        if (payload.status === 'paid' || payload.status === 'pending') {
            window.location.assign(payload.url || '/mi-cuenta/pedidos');
            return;
        }

        setError(payload.message || 'No pudimos procesar el pago. Inténtalo de nuevo.');
        setResult('');
        remountBrick();

        byId('mp-modal-error')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    window.addEventListener('resize', positionBrandBadge);

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
