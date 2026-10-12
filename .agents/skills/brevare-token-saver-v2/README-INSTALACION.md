# Brevare Token Saver v2 — instalación

Esta versión fue adaptada a la estructura y dependencias visibles en el archivo de proyecto recibido:
- PHP 8.3 / Laravel 13.8
- Livewire 3.6, Volt, Flux
- Blade, Vite 8, Tailwind CSS 4
- Módulos de pagos Culqi, Mercado Pago y Demo
- Skills existentes bajo `.agents/skills/`

## Instalación recomendada: dentro del repositorio Brevare

1. Descarga y extrae el ZIP.
2. Copia `.agents/skills/brevare-token-saver/` a la misma ruta dentro del repositorio Brevare.
3. Compara `AGENTS.md` incluido con el `AGENTS.md` actual. Integra las reglas que falten; no sobrescribas instrucciones específicas que ya tengas.
4. Reinicia Codex/ChatGPT Desktop si no detecta la skill.
5. En una tarea de Brevare, invócala como `$brevare-token-saver` si tu versión de Codex admite invocación por nombre; de lo contrario, pide: “Aplica la skill brevare-token-saver”.

## Instalación global opcional (Windows)

Copia `SKILL.md` a:

`%USERPROFILE%\\.codex\\skills\\brevare-token-saver\\SKILL.md`

La instalación local del repositorio suele ser preferible para que la skill esté junto al proyecto.

## Para qué sirve

Reduce lecturas innecesarias, exploración global, salidas largas, pruebas repetidas y carga accidental de skills no relacionadas. Incluye reglas especiales para pagos, checkout, inventario, base de datos y producción.

No cambia código por sí sola y no garantiza un porcentaje de ahorro. Comprueba el consumo real en tareas comparables.

## Nota de seguridad importante

El ZIP del proyecto recibido parece incluir `.env`, `.git`, `vendor/` y `node_modules/`. No compartas esos elementos en futuras exportaciones. No he inspeccionado los valores secretos del `.env`. Si contiene credenciales reales y el archivo se compartió fuera de un entorno de confianza, rota las claves API, credenciales de base de datos, correo y pasarelas de pago que pudieran estar expuestas. No cambies `APP_KEY` sin planificarlo, porque puede invalidar datos cifrados y sesiones.
