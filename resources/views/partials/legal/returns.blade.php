@php($company = config('brevare.company'))
<div class="space-y-8 text-sm leading-relaxed text-gray-600">
    <section>
        <h2 class="text-lg font-semibold text-gray-900">1. Alcance</h2>
        <p class="mt-2">
            Esta política regula los cambios, devoluciones y garantías de los productos adquiridos en
            <strong>Brevare</strong>, en concordancia con la Ley N° 29571, Código de Protección y Defensa
            del Consumidor.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">2. Plazo para solicitar un cambio o devolución</h2>
        <p class="mt-2">
            Cuentas con un plazo de <strong>7 días calendario</strong> desde la recepción del producto para
            solicitar un cambio o devolución, siempre que el producto se encuentre sin uso, en su empaque
            original y con todos sus accesorios.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">3. Causales aceptadas</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>El producto recibido no corresponde al pedido.</li>
            <li>El producto presenta fallas o defectos de fábrica.</li>
            <li>El producto llegó dañado por el transporte.</li>
            <li>El producto está incompleto o le faltan accesorios.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">4. Exclusiones</h2>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Productos personalizados, perecederos o de higiene personal abiertos.</li>
            <li>Productos usados, maltratados o sin su empaque original.</li>
            <li>Productos adquiridos en promociones marcadas como venta final.</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">5. Procedimiento</h2>
        <ol class="mt-2 list-decimal space-y-1 pl-5">
            <li>Escribe a <a href="mailto:{{ $company['support_email'] }}" class="font-medium text-gray-900 underline underline-offset-4">{{ $company['support_email'] }}</a> con tu número de pedido y motivo.</li>
            <li>Nuestro equipo evaluará tu caso y te brindará las instrucciones de recojo.</li>
            <li>Verificada la condición del producto, procederemos con el cambio o la devolución.</li>
        </ol>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-gray-900">6. Reembolsos</h2>
        <p class="mt-2">
            Los reembolsos se realizarán por el mismo medio de pago utilizado, en un plazo de hasta
            <strong>15 días hábiles</strong> contados desde la aprobación de la solicitud.
        </p>
    </section>
</div>
