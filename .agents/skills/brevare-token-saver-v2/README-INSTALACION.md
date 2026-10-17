# Brevare Token Saver v2 — instalación

Esta versión fue adaptada a la estructura y dependencias visibles en el archivo de proyecto recibido:
- PHP 8.3 / Laravel 13.8
- Livewire 3.6, Volt, Flux
- Blade, Vite 8, Tailwind CSS 4
- Módulos de pagos Culqi, Mercado Pago y Demo
- Skills existentes bajo `.agents/skills/`

## Instalación recomendada: dentro del repositorio Brevare

1. Descarga y extrae el ZIP.
2. Copia `.agents/skills/brevare-token-saver-v2/` a la misma ruta dentro del repositorio Brevare.
3. Compara `AGENTS.md` incluido con el `AGENTS.md` actual. Integra las reglas que falten; no sobrescribas instrucciones específicas que ya tengas.
4. Reinicia Codex/ChatGPT Desktop si no detecta la skill.
5. En una tarea de Brevare, invócala como `$brevare-token-saver-v2` si tu versión de Codex admite invocación por nombre; de lo contrario, pide: “Aplica la skill brevare-token-saver-v2”.

## Instalación global opcional (Windows)

Copia la carpeta completa a:

`%USERPROFILE%\\.codex\\skills\\brevare-token-saver-v2\\`

La instalación local del repositorio suele ser preferible para que la skill esté junto al proyecto.

## Para qué sirve

Reduce lecturas innecesarias, exploración global, salidas largas, pruebas repetidas y carga accidental de skills no relacionadas. Incluye reglas especiales para pagos, checkout, inventario, base de datos y producción.

No cambia código por sí sola y no garantiza un porcentaje de ahorro. Comprueba el consumo real en tareas comparables.

## Contenido actualizado (2026-10-17)

- `SKILL.md` — guía principal de Bravera/Brevare (checkout, Culqi, Mercado Pago, diseño y despliegue).
- `references/payments.md` — arquitectura de pagos, creación/reanudación de pedidos, cambio de pasarela, Culqi, Mercado Pago y webhooks.
- `references/checkout-ui.md` — sistema visual tipo Apple, Brick, selector de pasarela, chip de marca, cuotas y DNI.
- `references/verify-deploy.md` — pruebas, build, despliegue y verificación de assets.
- `references/incidents-2026-10-17.md` — historial de fallos corregidos y sus causas raíz.
- `references/working-style.md` — política original de bajo consumo de tokens, conservada y actualizada.

La copia anidada en `brevare-token-saver-v2/.agents/skills/brevare-token-saver/` es legado del ZIP y no se usa; su política está en `references/working-style.md`.

## Nota de seguridad importante

El ZIP del proyecto recibido parece incluir `.env`, `.git`, `vendor/` y `node_modules/`. No compartas esos elementos en futuras exportaciones. No he inspeccionado los valores secretos del `.env`. Si contiene credenciales reales y el archivo se compartió fuera de un entorno de confianza, rota las claves API, credenciales de base de datos, correo y pasarelas de pago que pudieran estar expuestas. No cambies `APP_KEY` sin planificarlo, porque puede invalidar datos cifrados y sesiones.
