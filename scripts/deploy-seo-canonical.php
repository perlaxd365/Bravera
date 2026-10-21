<?php

declare(strict_types=1);

$root = getcwd();

if (
    ! is_file($root.'/artisan')
    || ! is_dir($root.'/app')
    || ! is_dir($root.'/resources')
) {
    fwrite(STDERR, "Ejecuta este script desde la raíz de Laravel (~/Bravera).\n");
    exit(1);
}

$backupBase = rtrim((string) (getenv('HOME') ?: dirname($root)), '/');
$backupDir = $backupBase.'/backups/seo-canonical-'.date('Ymd-His');
$files = [
    'app/Http/Middleware/ForceCanonicalHost.php',
    'bootstrap/app.php',
    'config/seo.php',
    'resources/views/livewire/store/home.blade.php',
];

foreach ($files as $relativePath) {
    $source = $root.'/'.$relativePath;

    if (! is_file($source)) {
        continue;
    }

    $destination = $backupDir.'/'.$relativePath;
    $directory = dirname($destination);

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new RuntimeException("No se pudo crear el respaldo: {$directory}");
    }

    if (! copy($source, $destination)) {
        throw new RuntimeException("No se pudo respaldar: {$relativePath}");
    }
}

/**
 * @return string
 */
function readRequiredFile(string $path): string
{
    $content = file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException("No se pudo leer: {$path}");
    }

    return $content;
}

function writeFileAtomically(string $path, string $content): void
{
    $temporaryPath = $path.'.tmp-'.bin2hex(random_bytes(4));

    if (file_put_contents($temporaryPath, $content, LOCK_EX) === false) {
        throw new RuntimeException("No se pudo escribir temporalmente: {$path}");
    }

    if (! rename($temporaryPath, $path)) {
        @unlink($temporaryPath);
        throw new RuntimeException("No se pudo reemplazar: {$path}");
    }

    @chmod($path, 0644);
}

$middlewarePath = $root.'/app/Http/Middleware/ForceCanonicalHost.php';
$middleware = <<<'PHP'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceCanonicalHost
{
    /**
     * Keep the storefront on one crawlable origin.
     *
     * @var array<int, string>
     */
    private const STORE_HOSTS = ['brevare.com', 'www.brevare.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $canonicalUrl = rtrim(
            (string) config('seo.canonical_url', 'https://brevare.com'),
            '/',
        );
        $canonical = parse_url($canonicalUrl);
        $requestHost = strtolower($request->getHost());

        if (
            ! is_array($canonical)
            || empty($canonical['host'])
            || ! in_array($requestHost, self::STORE_HOSTS, true)
        ) {
            return $next($request);
        }

        $canonicalHost = strtolower($canonical['host']);
        $canonicalScheme = strtolower($canonical['scheme'] ?? 'https');
        $requestScheme = strtolower($request->getScheme());

        if ($requestHost === $canonicalHost && $requestScheme === $canonicalScheme) {
            return $next($request);
        }

        return redirect()->away(
            $canonicalScheme.'://'.$canonicalHost.$request->getRequestUri(),
            301,
        );
    }
}
PHP;

if (is_file($middlewarePath)) {
    if (! str_contains(readRequiredFile($middlewarePath), 'class ForceCanonicalHost')) {
        throw new RuntimeException('Ya existe un middleware distinto en ForceCanonicalHost.php.');
    }
} else {
    writeFileAtomically($middlewarePath, $middleware);
}

$configPath = $root.'/config/seo.php';
$config = readRequiredFile($configPath);

if (! str_contains($config, "'canonical_url'")) {
    $eol = str_contains($config, "\r\n") ? "\r\n" : "\n";
    $needle = 'return ['.$eol;

    if (! str_contains($config, $needle)) {
        throw new RuntimeException('No se encontró el inicio esperado de config/seo.php.');
    }

    $replacement = $needle
        ."    'canonical_url' => 'https://brevare.com',"
        .$eol.$eol;
    writeFileAtomically($configPath, str_replace($needle, $replacement, $config));
}

$bootstrapPath = $root.'/bootstrap/app.php';
$bootstrap = readRequiredFile($bootstrapPath);

if (! str_contains($bootstrap, 'use App\\Http\\Middleware\\ForceCanonicalHost;')) {
    $eol = str_contains($bootstrap, "\r\n") ? "\r\n" : "\n";
    $needle = 'use App\\Http\\Middleware\\EnsureVerifiedIfAuthenticated;'.$eol;

    if (! str_contains($bootstrap, $needle)) {
        throw new RuntimeException('No se encontró el bloque de imports esperado en bootstrap/app.php.');
    }

    $bootstrap = str_replace(
        $needle,
        $needle.'use App\\Http\\Middleware\\ForceCanonicalHost;'.$eol,
        $bootstrap,
    );
}

if (! str_contains($bootstrap, '$middleware->prepend(ForceCanonicalHost::class);')) {
    $eol = str_contains($bootstrap, "\r\n") ? "\r\n" : "\n";
    $needle = '->withMiddleware(function (Middleware $middleware): void {'.$eol;

    if (! str_contains($bootstrap, $needle)) {
        throw new RuntimeException('No se encontró withMiddleware() en bootstrap/app.php.');
    }

    $replacement = $needle
        .'        $middleware->prepend(ForceCanonicalHost::class);'
        .$eol.$eol;
    $bootstrap = str_replace($needle, $replacement, $bootstrap);
}

writeFileAtomically($bootstrapPath, $bootstrap);

$homePath = $root.'/resources/views/livewire/store/home.blade.php';
$home = readRequiredFile($homePath);

if (! str_contains($home, '<span class="sr-only">Brevare: </span>')) {
    $eol = str_contains($home, "\r\n") ? "\r\n" : "\n";
    $title = '<span x-text="slide.title"></span>';
    $highlight = '<span class="text-amber-700" x-text="slide.highlight"></span>';

    if (! str_contains($home, $title) || ! str_contains($home, $highlight)) {
        throw new RuntimeException('No se encontró el H1 esperado en la portada.');
    }

    $home = str_replace(
        $title,
        '<span class="sr-only">Brevare: </span>'
            .$eol.'                                    '
            .'<span x-text="slide.title">Regalos que llegan</span>',
        $home,
    );
    $home = str_replace(
        $highlight,
        '<span class="text-amber-700" x-text="slide.highlight">con ilusión</span>',
        $home,
    );
    writeFileAtomically($homePath, $home);
}

foreach ($files as $relativePath) {
    $path = $root.'/'.$relativePath;
    exec(PHP_BINARY.' -l '.escapeshellarg($path).' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException(
            "Error de sintaxis en {$relativePath}:\n".implode("\n", $output),
        );
    }
}

echo "Respaldo: {$backupDir}\n";
echo "Archivos SEO aplicados correctamente.\n";

foreach ([
    'php artisan optimize:clear',
    'php artisan optimize',
    'php artisan view:clear',
    'php artisan view:cache',
] as $command) {
    passthru($command, $exitCode);

    if ($exitCode !== 0) {
        throw new RuntimeException("Falló: {$command}");
    }
}

echo "Cachés regeneradas. Despliegue terminado.\n";
