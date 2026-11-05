# Conector MCP de Brevare

El conector MCP está integrado en la aplicación Laravel y expone las herramientas
de productos mediante Streamable HTTP.

## Endpoint

```text
https://brevare.com/mcp
```

El servidor original del ZIP requería `mcp.brevare.com`, Node.js, Nginx y systemd.
El hosting actual no ofrece esas piezas ni el subdominio. La integración Laravel
mantiene el mismo protocolo y herramientas sin añadir un proceso adicional.

Las herramientas llaman directamente a los servicios y validaciones internas de
Laravel. No realizan solicitudes a la URL pública ni pasan `PRODUCT_API_PASSWORD`
por la URL o por logs de acceso.

## Autenticación

Las solicitudes MCP requieren:

```http
Authorization: Bearer <MCP_ACCESS_TOKEN>
Content-Type: application/json
Accept: application/json, text/event-stream
```

`MCP_ACCESS_TOKEN` es independiente de `PRODUCT_API_PASSWORD`. Debe tener al menos
32 caracteres y nunca se incluye en la URL.

Los orígenes permitidos se configuran con `MCP_ALLOWED_ORIGINS`. Las llamadas
server-to-server sin cabecera `Origin` están permitidas.

## Herramientas

| Herramienta | Uso |
| --- | --- |
| `consultar_catalogo` | Categorías, marcas, proveedores, atributos y límites |
| `consultar_producto` | Producto por ID o slug, con stock y readiness |
| `publicar_producto` | Crea y publica un producto completo |

`publicar_producto` no reintenta automáticamente. Los errores de validación se
devuelven como `isError: true` con el detalle de la API.

## Inicialización

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "initialize",
  "params": {
    "protocolVersion": "2025-06-18",
    "capabilities": {},
    "clientInfo": {
      "name": "chatgpt",
      "version": "1.0.0"
    }
  }
}
```

## Métodos disponibles

- `initialize`
- `ping`
- `tools/list`
- `tools/call`
- Notificaciones MCP sin `id`, que responden `202`

## Healthcheck

```bash
curl https://brevare.com/mcp/health
```

La respuesta solo informa si el conector y la API interna están configurados;
no expone secretos.

## ChatGPT

Registra el servidor como conector remoto con la URL:

```text
https://brevare.com/mcp
```

Selecciona autenticación Bearer y usa el token configurado en
`MCP_ACCESS_TOKEN`. La publicación de escritura desde ChatGPT depende de que el
plan o espacio de trabajo permita conectores MCP con acciones de escritura. El
plan Go puede no habilitar esta función.

## Variables

```dotenv
PRODUCT_API_PASSWORD=<ALEATORIA>
MCP_ACCESS_TOKEN=<ALEATORIO_DE_AL_MENOS_32_CARACTERES>
MCP_ALLOWED_ORIGINS=https://chatgpt.com,https://www.chatgpt.com,https://chat.openai.com
```
