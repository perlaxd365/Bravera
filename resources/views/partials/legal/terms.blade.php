@php($company = config('brevare.company'))
<div class="space-y-8 text-sm leading-relaxed text-gray-600">
    <section>
        <h2 class="text-lg font-semibold text-gray-900">1. Información general</h2>
        <p class="mt-2">
            Bienvenido a <strong>Brevare</strong>, tienda online de moda, calzado y accesorios con envío a todo el Perú.
            El uso de este sitio web implica la aceptación plena de los presentes Términos y Condiciones.
            Si no estás de acuerdo con ellos, te pedimos abstenerte de utilizar el sitio web.
        </p>
        <p class="mt-2">
            Razón social: {{ $company['legal_name'] }} · RUC: {{ $company['ruc'] }} · Domicilio: {{ $company['address'] }}, distrito de {{ $company['city'] }}, provincia y departamento de {{ $company['region'] }}, {{ $company['country_name'] }}.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">2. Capacidad legal</h2>
        <p class="mt-2">
            Para realizar compras en Brevare debes ser mayor de 18 años y contar con capacidad legal para contratar.
            Al registrarte declaras que la información proporcionada es veraz, completa y actualizada.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">3. Productos y precios</h2>
        <p class="mt-2">
            Los precios publicados están expresados en Soles (S/) e incluyen el IGV cuando corresponda.
            Brevare se reserva el derecho de modificar precios, promociones y disponibilidad sin previo aviso.
            Las imágenes son referenciales y pueden presentar ligeras variaciones respecto al producto real.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">4. Pedidos y pagos</h2>
        <p class="mt-2">
            Los pagos se procesan a través de la pasarela <strong>Culqi</strong> y medios habilitados
            (tarjeta de crédito/débito, Yape, Plin y efectivo). El pedido se considera confirmado una vez
            acreditado el pago. Brevare podrá cancelar pedidos ante indicios de fraude o error manifiesto.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">5. Envíos y entregas</h2>
        <p class="mt-2">
            Los plazos de entrega son referenciales y dependen de la ciudad de destino y del courier.
            Una vez despachado el pedido, recibirás un correo con la información de seguimiento.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">6. Productos digitales y contenido descargable</h2>
        <p class="mt-2">
            Los productos digitales incluyen, entre otros, archivos PDF, e-books, manuales, plantillas,
            videos, cursos, membresías y cualquier contenido descargable o habilitado por Brevare.
            La entrega se realiza mediante descarga o acceso vinculado a la cuenta del comprador.
        </p>
        <p class="mt-2">
            <strong>Política de no devolución:</strong> una vez entregado el archivo o habilitado el acceso,
            no se aceptan cambios, devoluciones ni reembolsos por arrepentimiento, descarga, falta de uso,
            compra accidental o incompatibilidad con expectativas, en la medida permitida por ley.
            El comprador debe revisar antes del pago el formato, los requisitos y el alcance del contenido.
        </p>
        <p class="mt-2">
            Esta política no limita los derechos que correspondan cuando el contenido no sea entregado,
            esté incompleto o defectuoso, no corresponda a lo descrito o se haya producido un cobro duplicado.
            En esos casos, Brevare evaluará la reposición del acceso, la corrección del contenido o el
            reembolso que resulte aplicable conforme a la Ley N° 29571.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">7. Derechos del consumidor</h2>
        <p class="mt-2">
            Como consumidor cuentas con los derechos reconocidos por el Código de Protección y Defensa del
            Consumidor (Ley N° 29571). Para cualquier consulta o reclamo puedes escribir a
            <a href="mailto:{{ $company['support_email'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['support_email'] }}</a>
            o registrar tu caso en nuestro Libro de Reclamaciones.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">8. Propiedad intelectual</h2>
        <p class="mt-2">
            Todos los contenidos del sitio (marcas, logotipos, textos, imágenes y software) son propiedad de
            Brevare o de terceros que han autorizado su uso, y se encuentran protegidos por la legislación
            vigente sobre propiedad intelectual.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">9. Modificaciones</h2>
        <p class="mt-2">
            Brevare podrá actualizar estos Términos y Condiciones en cualquier momento. La versión vigente será
            siempre la publicada en esta página.
        </p>
    </section>
</div>
