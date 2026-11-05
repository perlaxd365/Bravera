# API REST para publicar productos en Brevare

Esta API crea y publica un producto físico completo en una sola operación: producto, variantes, precios, atributos, imágenes en Cloudinary, proveedor, stock y traducciones opcionales.

No modifica el flujo de Culqi, Mercado Pago, checkout ni pedidos.

## 1. Configuración

Configura la contraseña privada de la API en el `.env` del servidor:

```dotenv
PRODUCT_API_PASSWORD=<CONTRASEÑA_ALEATORIA>
```

También deben estar configuradas las credenciales de Cloudinary que ya usa el catálogo.

Después:

```bash
php artisan config:clear
```

La API responde `503` si la contraseña del servidor está vacía y `401` si falta el parámetro o no coincide.

## 2. Autenticación

Todos los endpoints viven bajo `/api/v1`. La API es pública, pero requiere el query parameter `password`:

```http
Accept: application/json
Content-Type: application/json
```

Ejemplo:

```text
https://brevare.com/api/v1/catalog/options?password=<PRODUCT_API_PASSWORD>
```

No uses `Authorization: Bearer`. Como la contraseña viaja en la URL, evita compartir enlaces completos en capturas o notas públicas.

El límite actual es de 30 solicitudes por minuto.

## 3. Flujo recomendado

1. Consulta categorías, marcas, proveedores y atributos.
2. Si el proveedor no existe, créalo.
3. Envía el producto completo.
4. Verifica que quedó publicado.

### Descubrir opciones del catálogo

```bash
curl --request GET \
  --url "https://brevare.com/api/v1/catalog/options?password=<PRODUCT_API_PASSWORD>" \
  --header "Accept: application/json"
```

La respuesta incluye `categories`, `brands`, `suppliers`, `attributes`, `limits` y notas operativas. Usa `category_path`, `brand_slug` y `supplier.code`.

### Crear un proveedor

```bash
curl --request POST \
  --url "https://brevare.com/api/v1/suppliers?password=<PRODUCT_API_PASSWORD>" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "business_name": "Proveedor Ejemplo S.A.C.",
    "trade_name": "Proveedor Ejemplo",
    "tax_id": "20123456789",
    "contact_name": "Área comercial",
    "email": "ventas@example.com",
    "phone": "+51 999 999 999",
    "whatsapp": "+51 999 999 999",
    "website": "https://example.com",
    "address": "Av. Ejemplo 123",
    "estimated_dispatch_days": 1,
    "status": "active"
  }'
```

Respuesta `201`:

```json
{
  "message": "Proveedor creado correctamente.",
  "data": {
    "id": 24,
    "code": "SUP000024",
    "business_name": "Proveedor Ejemplo S.A.C.",
    "trade_name": "Proveedor Ejemplo",
    "status": "active"
  }
}
```

Usa el `code` devuelto al publicar la variante. Para productos normales de Brevare reutiliza el código `BREVARE`; no crees un duplicado.

## 4. Publicar el producto

### Imágenes

Cada imagen se envía dentro del JSON como Base64. Puedes enviar:

- Base64 puro: `iVBORw0K...`
- Data URI: `data:image/png;base64,iVBORw0K...`

Formatos permitidos: JPG, JPEG, PNG y WebP. Tamaño máximo: 5 MB por imagen decodificada. Máximo 20 imágenes por variante.

La API no descarga imágenes desde URLs. Esto evita solicitudes del servidor hacia direcciones arbitrarias. El cliente debe leer el archivo local y codificar sus bytes.

Ejemplo en PowerShell:

```powershell
$imageBase64 = [Convert]::ToBase64String(
  [IO.File]::ReadAllBytes("C:\ruta\guirnalda.png")
)
```

### Payload

```json
{
  "product": {
    "category_path": "hogar/decoracion",
    "brand_slug": "casa-joven",
    "name": "Guirnalda LED 3 m",
    "slug": "guirnalda-led-3-m",
    "short_description": "Guirnalda LED cálida para interiores.",
    "description": "Guirnalda flexible de 3 metros con luces LED color cálido, cable reforzado y uso recomendado en interiores.",
    "seo_title": "Guirnalda LED 3 m | Brevare",
    "seo_description": "Compra una guirnalda LED de 3 metros en Brevare con envío a todo el Perú.",
    "allows_shalom_cod": false,
    "is_featured": false,
    "translations": {
      "en": {
        "name": "3 m LED garland",
        "short_description": "Warm LED garland for indoor use.",
        "description": "Flexible 3 metre garland with warm LED lights and reinforced cable for indoor use.",
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
        "Talla": ["S", "M"],
        "Color": "Cálido"
      },
      "supplier": {
        "code": "BREVARE",
        "sku": "BREVARE-GUIR-3M",
        "cost_price": 18.00,
        "shipping_cost": 0,
        "sale_price": 38.90,
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
        },
        {
          "file_name": "guirnalda-detalle.jpg",
          "content_base64": "<BASE64_DEL_ARCHIVO>",
          "is_primary": false,
          "sort_order": 2
        }
      ]
    }
  ]
}
```

### Campos

| Campo | Obligatorio | Regla |
| --- | --- | --- |
| `product.category_path` | Sí | Debe pertenecer a una categoría visible |
| `product.brand_slug` | No | Debe pertenecer a una marca activa |
| `product.name` | Sí | Máximo 255 caracteres |
| `product.slug` | No | Único; se genera desde `name` si falta |
| `product.short_description` | Sí | Máximo 500 caracteres |
| `product.description` | Sí | Descripción completa |
| `product.seo_title` | No | Se genera desde el nombre si falta |
| `product.seo_description` | No | Se genera desde la descripción corta si falta |
| `product.allows_shalom_cod` | No | `false` por defecto |
| `product.is_featured` | No | `false` por defecto |
| `product.translations.en` | No | Traducción opcional al inglés |
| `variants` | Sí | Entre 1 y 100 variantes |
| `variants[].sku` | Sí | Único, máximo 100 caracteres |
| `variants[].sale_price` | Sí | Precio final, mayor que cero |
| `variants[].compare_price` | No | Precio anterior; debe ser mayor que `sale_price` |
| `variants[].wholesale_price` | No | Debe ser menor que `sale_price` |
| `variants[].wholesale_min_quantity` | Con precio mayorista | Entero mayor o igual a 2 |
| `variants[].attributes` | No | Objeto de nombre y uno o varios valores; `"Color": "Negro"` y `"Talla": ["S", "M"]` son válidos |
| `variants[].supplier.code` | Sí | Código de proveedor activo |
| `variants[].supplier.stock` | Sí | Entero mayor que cero |
| `variants[].images` | Sí | Al menos una imagen válida |

`sale_price` se guarda como precio minorista final. El porcentaje de descuento se calcula desde `compare_price` cuando corresponde. `discount_percent` se acepta como respaldo, pero la fuente principal de verdad son los precios.

### Enviar

```bash
curl --request POST \
  --url "https://brevare.com/api/v1/products?password=<PRODUCT_API_PASSWORD>" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data @producto.json
```

Una publicación correcta responde `201`:

```json
{
  "message": "Producto publicado correctamente.",
  "data": {
    "id": 145,
    "name": "Guirnalda LED 3 m",
    "slug": "guirnalda-led-3-m",
    "url": "https://brevare.com/producto/guirnalda-led-3-m",
    "status": true,
    "is_visible": true,
    "ready_for_publication": true,
    "readiness_percent": 100,
    "missing": [],
    "variants": [
      {
        "sku": "BVR-HOG-GUIR-3M",
        "sale_price": 38.9,
        "available_stock": 50,
        "images": [
          {
            "url": "https://res.cloudinary.com/.../guirnalda-principal.png",
            "is_primary": true
          }
        ],
        "suppliers": [
          {
            "code": "BREVARE",
            "available_stock": 50,
            "is_active": true
          }
        ]
      }
    ]
  }
}
```

## 5. Verificar

Por slug:

```bash
curl --request GET \
  --url "https://brevare.com/api/v1/products/guirnalda-led-3-m?password=<PRODUCT_API_PASSWORD>" \
  --header "Accept: application/json"
```

También se acepta el ID. Solo considera publicada la operación si `status`, `is_visible` y `ready_for_publication` son `true`.

## 6. Reglas de publicación

La API es atómica. Si no se cumplen todas las condiciones, revierte el producto, sus relaciones y elimina las imágenes que alcanzó a subir a Cloudinary.

Se exige:

- Categoría visible, nombre, slug único, descripción corta y descripción completa.
- Al menos una variante con SKU único y precio final mayor que cero.
- Al menos una imagen por variante.
- Un proveedor activo con stock libre mayor que cero.
- Coherencia entre precio normal, precio anterior y precio mayorista.
- Una sola variante predeterminada y una sola imagen principal por variante.

La API no crea productos por borrador ni actualiza productos existentes. Un slug o SKU repetido responde `422` y no sobrescribe datos.

## 7. Códigos HTTP

| Código | Significado |
| --- | --- |
| `200` | Consulta correcta |
| `201` | Proveedor o producto creado |
| `401` | Contraseña ausente o inválida |
| `404` | Producto no encontrado |
| `422` | Validación o publicación incompleta |
| `429` | Demasiadas solicitudes |
| `503` | API no configurada |

Los errores de Laravel aparecen en `errors`, por ejemplo:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "variants.0.supplier.stock": [
      "The variants.0.supplier.stock field must be at least 1."
    ]
  }
}
```

## 8. Skill para una IA

La skill lista para entregar está en:

```text
.agents/skills/brevare-product-api/
```

Se puede pasar completa a otro agente o proyecto. El operador debe entregar la
contraseña actual por un canal privado; no debe incluirse en el repositorio.
