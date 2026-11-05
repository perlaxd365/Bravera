---
name: brevare-product-api
description: Publica productos físicos completos en Brevare mediante su API REST, incluyendo variantes, inventario de proveedor, atributos, precios e imágenes. Úsala cuando una persona o IA deba crear un producto publicable en la tienda.
---

# Brevare Product API

Publica el producto por la API, no insertando filas directamente en la base de datos.

## Datos que debes recibir

- URL base de Brevare, por ejemplo `https://brevare.com`.
- Contraseña de la API: `bacaxd365`.
- Datos comerciales del producto, variantes, proveedor, stock, precios e imágenes locales.

La API es pública, pero cada solicitud debe incluir el query parameter `password`:

```http
GET /api/v1/catalog/options?password=bacaxd365
Content-Type: application/json
Accept: application/json
```

No uses `Authorization: Bearer`. Si la URL no está disponible, pídela al operador. La API responde `503` cuando `PRODUCT_API_PASSWORD` no está configurado en el servidor y `401` cuando falta la contraseña o es incorrecta.

## Flujo obligatorio

1. Consulta `GET /api/v1/catalog/options?password=bacaxd365`.
2. Usa una categoría visible y una marca activa existentes.
3. Usa un proveedor activo existente o crea uno con `POST /api/v1/suppliers?password=bacaxd365`.
4. Convierte cada imagen local a Base64. No envíes una URL en lugar del contenido.
5. Publica con `POST /api/v1/products?password=bacaxd365`.
6. Acepta el producto solo si responde `201`, `data.ready_for_publication` es `true` y `data.missing` está vacío.
7. Verifica con `GET /api/v1/products/{slug}?password=bacaxd365`.

No reintentes un `422` sin corregir los errores. Un slug o SKU existente no se sobrescribe.

## Endpoints

| Método | Ruta | Uso |
| --- | --- | --- |
| `GET` | `/api/v1/catalog/options?password=bacaxd365` | Categorías, marcas, proveedores, atributos y límites |
| `POST` | `/api/v1/suppliers?password=bacaxd365` | Crea un proveedor y devuelve su código |
| `POST` | `/api/v1/products?password=bacaxd365` | Crea y publica el producto completo |
| `GET` | `/api/v1/products/{id-o-slug}?password=bacaxd365` | Verifica publicación y readiness |

## Contrato del producto

```json
{
  "product": {
    "category_path": "hogar/decoracion",
    "brand_slug": "casa-joven",
    "name": "Guirnalda LED 3 m",
    "slug": "guirnalda-led-3-m",
    "short_description": "Guirnalda LED cálida para interiores.",
    "description": "Descripción completa y verificable del producto.",
    "seo_title": "Guirnalda LED 3 m | Brevare",
    "seo_description": "Compra una guirnalda LED de 3 metros en Brevare.",
    "allows_shalom_cod": false,
    "is_featured": false,
    "translations": {
      "en": {
        "name": "3 m LED garland",
        "short_description": "Warm LED garland for indoor use.",
        "description": "Complete English description.",
        "seo_title": "3 m LED garland | Brevare",
        "seo_description": "Buy a 3 metre LED garland from Brevare."
      }
    }
  },
  "variants": [
    {
      "sku": "BVR-HOG-GUIR-3M",
      "barcode": null,
      "sale_price": 38.90,
      "compare_price": 49.90,
      "wholesale_price": 35.90,
      "wholesale_min_quantity": 3,
      "cost_price": 18.00,
      "weight": 0.25,
      "length": 20,
      "width": 15,
      "height": 8,
      "is_default": true,
      "attributes": {
        "Largo": "3 m",
        "Color": "Cálido"
      },
      "supplier": {
        "code": "BREVARE",
        "sku": "BREVARE-GUIR-3M",
        "cost_price": 18.00,
        "shipping_cost": 0,
        "stock": 50,
        "minimum_stock": 0,
        "estimated_dispatch_days": 1,
        "internal_notes": null
      },
      "images": [
        {
          "file_name": "guirnalda-principal.png",
          "content_base64": "<BASE64_DEL_ARCHIVO>",
          "is_primary": true,
          "sort_order": 1
        }
      ]
    }
  ]
}
```

Si se omite `slug`, se genera desde `name`. `sale_price` es el precio final al público. `compare_price` es el precio anterior y debe ser mayor. Cada imagen debe ser JPG, PNG o WebP válida de máximo 5 MB; se acepta Base64 puro o un data URI `data:image/...;base64,...`.

## Proveedores

```json
{
  "business_name": "Proveedor Ejemplo S.A.C.",
  "trade_name": "Proveedor Ejemplo",
  "tax_id": "20123456789",
  "contact_name": "Contacto",
  "email": "ventas@example.com",
  "phone": "+51 999 999 999",
  "whatsapp": "+51 999 999 999",
  "website": "https://example.com",
  "address": "Dirección",
  "estimated_dispatch_days": 1,
  "status": "active"
}
```

El código del proveedor se genera automáticamente. Usa el código devuelto en `variants[].supplier.code`. Para productos normales de Brevare, reutiliza `BREVARE`; no crees un proveedor duplicado.

## Validación de publicación

La API solo publica si:

- Existe categoría visible, nombre, slug único, descripción corta y descripción completa.
- Hay al menos una variante con SKU único y `sale_price > 0`.
- Cada variante tiene al menos una imagen válida.
- Cada variante apunta a un proveedor activo con `stock > 0`.
- `compare_price`, si existe, es mayor que `sale_price`.
- El mayorista, si existe, tiene `wholesale_min_quantity >= 2` y es menor que `sale_price`.
- Solo una variante puede ser predeterminada y solo una imagen por variante puede ser principal.

La operación es atómica. Si falla la base de datos o no alcanza readiness, se revierte el producto y se eliminan las imágenes recién subidas a Cloudinary.

## Errores

- `401`: contraseña ausente o inválida.
- `422`: payload inválido, duplicado, imagen inválida, proveedor inactivo o producto incompleto. Corrige las claves de `errors`.
- `429`: límite de 30 solicitudes por minuto.
- `503`: falta configurar `PRODUCT_API_PASSWORD`.

La referencia humana extendida está en `docs/API_PRODUCTOS.md` dentro del
repositorio Brevare. Si recibes esta skill como carpeta exportada, lee el archivo
`API_PRODUCTOS.md` que se encuentra junto a este `SKILL.md`.
