@php($company = config('brevare.company'))
<div class="space-y-8 text-sm leading-relaxed text-gray-600">
    <section>
        <h2 class="text-lg font-semibold text-gray-900">1. Responsable del tratamiento</h2>
        <p class="mt-2">
            <strong>{{ $company['legal_name'] }}</strong>, con domicilio en {{ $company['address'] }}, distrito de {{ $company['city'] }}, provincia y departamento de {{ $company['region'] }}, {{ $company['country_name'] }}, es responsable
            del tratamiento de los datos personales que recopila a través de este sitio web, conforme a la
            Ley N° 29733, Ley de Protección de Datos Personales, y su reglamento.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">2. Datos que recopilamos</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Datos de identificación: nombre, DNI y datos de contacto.</li>
            <li>Datos de contacto: correo electrónico y número de teléfono.</li>
            <li>Datos de entrega: direcciones de envío y referencias.</li>
            <li>Datos de navegación, cookies y medición publicitaria.</li>
            <li>Información de pagos procesada por las pasarelas Culqi y Mercado Pago (no almacenamos datos de tarjeta).</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">3. Finalidad del tratamiento</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Gestionar tu registro y cuenta de usuario.</li>
            <li>Procesar pedidos, pagos, envíos y devoluciones.</li>
            <li>Enviarte comunicaciones sobre el estado de tus pedidos.</li>
            <li>Atender consultas, reclamos y solicitudes de soporte.</li>
            <li>Mejorar nuestros productos y servicios.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">4. Conservación de los datos</h2>
        <p class="mt-2">
            Los datos se conservarán mientras exista una relación contractual o el tiempo necesario para cumplir
            con obligaciones legales, contables y tributarias.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">5. Tus derechos</h2>
        <p class="mt-2">
            Puedes ejercer tus derechos de acceso, rectificación, cancelación y oposición (ARCO) escribiendo a
            <a href="mailto:{{ $company['support_email'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['support_email'] }}</a>.
            Atenderemos tu solicitud en los plazos establecidos por ley.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">6. Seguridad</h2>
        <p class="mt-2">
            Aplicamos medidas técnicas y organizativas razonables para proteger tus datos personales contra
            accesos no autorizados, pérdida o alteración.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">7. Cookies</h2>
        <p class="mt-2">
            Utilizamos cookies y tecnologías similares para recordar tus preferencias, mantener tu sesión y
            analizar el uso del sitio. Las cookies necesarias permiten que la tienda funcione; las cookies de
            medición y publicidad solo se activan cuando otorgas tu consentimiento.
        </p>
        <p class="mt-2">
            Con tu consentimiento, usamos Meta Pixel y la Conversions API de Meta Platforms para medir visitas,
            eventos de catálogo, carrito y compras, y mejorar nuestras campañas. Para mejorar la coincidencia de
            eventos podemos enviar a Meta identificadores seudonimizados (como correo y teléfono cifrados con
            SHA-256), cookies de Meta, dirección IP y agente del navegador. No enviamos datos de tarjeta ni
            contraseñas.
        </p>
        <p class="mt-2">
            También usamos Google Analytics 4 y una medición interna de visitas para conocer cuántas personas
            entran a la tienda y qué páginas consultan. La medición interna utiliza un identificador aleatorio
            de navegador y no almacena tu dirección IP ni tu agente del navegador.
        </p>
        <p class="mt-2">
            Puedes aceptar o rechazar las cookies no necesarias desde el aviso de cookies y cambiar tu elección
            en “Preferencias de cookies” al pie del sitio.
        </p>
    </section>
</div>
