# Lanzamiento de Brevare en https://brevare.com

Guía operativa para pasar el proyecto a producción en hosting **GoDaddy** y
dejarlo listo para indexar productos en Google, operar WhatsApp con clientes y
cobrar con Culqi.

---

## 1. Qué ya quedó implementado

- **SEO por producto (automático):** JSON-LD `Product` + `Offer` + `AggregateRating`,
  título/descripción, canonical por variante, Open Graph/Twitter, `robots`.
- **Sitemap** (`/sitemap.xml`) y **feed de Google Merchant** (`/feeds/google-shopping.xml`).
- **SEO por defecto:** al guardar un producto sin título/descripción SEO se generan
  automáticamente desde el nombre y la descripción.
- **Panel Clientes** (`/panel/customers`) con búsqueda y enlaces de WhatsApp con el
  estado del último pedido.
- **Panel Portada** (`/panel/settings/homepage`) para portadas e imágenes de categorías.
- **Moderación de reseñas** (`/panel/catalog/reviews`): las opiniones quedan pendientes
  y se aprueban/rechazan antes de mostrarse.
- **Páginas legales + Libro de Reclamaciones** con formulario y datos reales de empresa
  centralizados en `config/brevare.php`.
- **Datos de empresa** fuente única: `COMPANY_*` en `.env` → `config/brevare.php`.

---

## 2. Requisitos del servidor (GoDaddy)

- PHP **8.3** con extensiones: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `gd`
  (o `imagick`), `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `zip`.
- Composer (para instalar dependencias; si GoDaddy no lo permite, subir `vendor/`).
- Node.js **solo en local** para compilar assets (en el hosting no hace falta).
- MySQL (crear base de datos y usuario desde cPanel).
- SSL activo para `brevare.com` y `www.brevare.com`.

---

## 3. Pasos de despliegue

1. **Compilar assets en local** y subirlos:
   ```bash
   npm install
   npm run build      # genera /public/build
   ```
2. **Subir el proyecto** (sin `node_modules`, sin `.git`, sin `.env` local).
   Se puede subir `vendor/` ya instalado para evitar Composer en el hosting:
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
3. **Document root → carpeta `/public`.**
   En cPanel: mueve el contenido de `public/` o configura el dominio apuntando a
   `public`. Si no es posible, usa un `.htaccess` en la raíz que reescriba a `public`.
4. **Crear `.env` de producción** (ver sección 4).
5. **Generar key y migrar:**
   ```bash
   php artisan key:generate
   php artisan migrate --force
   ```
6. **Permisos:** `storage/` y `bootstrap/cache/` con escritura (755/775).
7. **Optimizar cachés:**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   Tras cualquier cambio de `.env` o rutas: `php artisan optimize:clear`.
8. **Storage link** (solo si algún día usas disco local):
   `php artisan storage:link`.

---

## 4. `.env` de producción (valores clave)

```dotenv
APP_NAME=Brevare
APP_ENV=production
APP_DEBUG=false
APP_KEY=            # php artisan key:generate
APP_URL=https://brevare.com
APP_TIMEZONE=America/Lima
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_PE

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=...      # cPanel
DB_USERNAME=...      # cPanel
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=.brevare.com

QUEUE_CONNECTION=database
CACHE_STORE=database

# Datos legales (footer, páginas legales y SEO)
COMPANY_LEGAL_NAME="Inversiones La Breña S.A.C."
COMPANY_RUC=10738883123
COMPANY_EMAIL=administracion@brevare.com
COMPANY_SUPPORT_EMAIL=administracion@brevare.com
COMPANY_PHONE="+51 902 517 849"
COMPANY_PHONE_E164=+51902517849
COMPANY_WHATSAPP=51902517849
COMPANY_ADDRESS="Avenida Camino Real 456"
COMPANY_CITY="San Isidro"
COMPANY_REGION="Lima"
COMPANY_COUNTRY=PE
COMPANY_COUNTRY_NAME="Perú"

# Correo
MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtpout.secureserver.net
MAIL_PORT=465
MAIL_USERNAME=administracion@brevare.com
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="administracion@brevare.com"
MAIL_FROM_NAME="Brevare"
MAIL_ADMIN_ADDRESS="administracion@brevare.com"

# Imágenes
CLOUDINARY_CLOUD_NAME=...
CLOUDINARY_API_KEY=...
CLOUDINARY_API_SECRET=...
CLOUDINARY_SECURE=true

# Pagos (producción)
PAYMENT_GATEWAY=culqi
PAYMENT_ALLOW_DEMO=false
CULQI_PUBLIC_KEY=pk_live_...
CULQI_SECRET_KEY=sk_live_...
CULQI_WEBHOOK_URL=https://brevare.com/webhooks/culqi
CULQI_CAPTURE=true
CULQI_TIMEOUT=20
CULQI_YAPE_EXPIRATION_MINUTES=30
```

---

## 5. Tareas programadas (cron en cPanel)

Los correos se envían por cola (`QUEUE_CONNECTION=database`). En hosting compartido
no hay workers persistentes, así que programa:

```cron
* * * * * cd /home/USUARIO/brevare && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /home/USUARIO/brevare && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

Reemplaza `USUARIO` y la ruta real. Con esto los correos de pedidos, verificación y
bienvenida salen cada minuto.

---

## 6. Google Search Console

1. Entra a <https://search.google.com/search-console> con la cuenta de `brevare.com`.
2. Crea una propiedad tipo **Dominio** → `brevare.com`.
3. Verifica por **DNS** (agrega el registro TXT que te da Google en el DNS de tu
   proveedor de dominio).
4. En **Sitemaps**, envía: `https://brevare.com/sitemap.xml`.
5. Usa **Inspección de URL** sobre un producto para solicitar indexación inicial.

---

## 7. Google Merchant Center (fichas con foto, precio y detalle)

1. Crea la cuenta en <https://merchants.google.com>.
2. Verifica y reclama el dominio `brevare.com`.
3. **Fuentes de datos → Agregar feed programado**:
   `https://brevare.com/feeds/google-shopping.xml`.
4. Configura:
   - País: Perú · Moneda: PEN.
   - Envíos e impuestos (según tus tarifas).
   - Enlace la cuenta de Google Ads si harás campañas.
5. Revisa el informe de productos para corregir avisos (imágenes, GTIN, categoría).

> Nota: Google decide cuándo mostrar resultados enriquecidos; los datos estructurados
> y el feed hacen que la página sea *elegible*, no garantizan una posición.

---

## 8. Correo corporativo (entrega confiable)

En el DNS de `brevare.com` configura:

- **SPF** (TXT): incluir el servidor de GoDaddy, p. ej.
  `v=spf1 include:secureserver.net -all`.
- **DKIM**: activar la firma DKIM desde el panel de correo de GoDaddy.
- **DMARC** (TXT): `v=DMARC1; p=none; rua=mailto:administracion@brevare.com`.

Prueba envíos a Gmail/Outlook y revisa que no caigan en spam.

---

## 9. Culqi (pagos en producción)

1. En el panel de Culqi pasa a **modo producción** y obtén `pk_live_` y `sk_live_`.
2. Configura `CULQI_PUBLIC_KEY`, `CULQI_SECRET_KEY` y
   `CULQI_WEBHOOK_URL=https://brevare.com/webhooks/culqi`.
3. Verifica el dominio para la **verificación de comercio** (Culqi/Merchant).
4. Deja `PAYMENT_ALLOW_DEMO=false`.
5. **Haz una compra de prueba real de bajo monto** y confirma que el pedido pasa a
   `paid` y que llegan los correos.

---

## 10. Después de limpiar el catálogo (ya ejecutado)

El comando `php artisan catalog:reset` ya se ejecutó: hay **0 productos**, **26
categorías** y la configuración de portada intacta. Antes de publicar productos,
hazlo en este orden:

1. Crea al menos **un proveedor** (panel → Catálogo → Proveedores).
2. Crea la **marca** u opcional.
3. Crea las **zonas de envío** y **tarifas** (panel → Envíos). *Sin tarifas el
   checkout no puede cotizar.*
4. Agrega **productos** con al menos una variante, una foto y stock del proveedor.
5. Marca el producto como **Visible** para que aparezca en tienda, sitemap y feed.
6. Solicita indexación en Search Console para los primeros productos.

---

## 11. Pendientes conocidos (antes de cobrar de verdad)

- **3 tests de checkout/Culqi fallan** (`tests/Feature/Store/CheckoutTest.php`,
  líneas 1316, 1391, 1979): flujo de "retomar pago" / modal Culqi. No bloquean el
  despliegue, pero **hay que validar una compra real de punta a punta** antes de
  aceptar pagos con tarjeta/Yape. Decidido dejar para después.
- Revisar en Culqi la variante de **Yape** (orden pendiente vs. fallida).

---

## 12. Verificación post-lanzamiento

- [ ] `https://brevare.com` carga con candado (HTTPS) y sin errores.
- [ ] `https://brevare.com/sitemap.xml` responde 200 y lista categorías/productos.
- [ ] `https://brevare.com/feeds/google-shopping.xml` responde y trae productos con foto/precio.
- [ ] Un producto muestra título, imagen y precio en resultado enriquecido (Rich Results Test).
- [ ] Registro de cliente, verificación por correo y correo de bienvenida funcionan.
- [ ] Alta de un pedido dispara correos (estado) y aparece en `/panel` y en Clientes.
- [ ] `/panel/catalog/reviews` permite moderar reseñas.
- [ ] WhatsApp desde Clientes abre con el mensaje y estado correctos.
- [ ] `APP_DEBUG=false` (ningún stack trace visible) y logs en `storage/logs`.
- [ ] Backups automáticos de base de datos y archivos configurados en GoDaddy.
