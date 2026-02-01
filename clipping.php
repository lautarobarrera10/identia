<?php
/**
 * CLIPPING AUTOMÁTICO BÁSICO
 * ----------------------------------
 * - Recibe URLs por formulario
 * - Extrae dominio, título y bajada
 * - Fecha del día
 */

// ===================== LÓGICA (SIN CAMBIOS) =====================

function obtenerDominio($url) {
    $host = parse_url($url, PHP_URL_HOST);
    $host = preg_replace('/^www\./', '', $host);
    $partes = explode('.', $host);
    $sitio = strtoupper($partes[0]);

    $reemplazos = [
        'LANACION' => 'LA NACIÓN',
        'PAGINA12' => 'PÁGINA 12',
        'CLARIN' => 'CLARÍN',
        "AMBITO" => 'ÁMBITO',
        "CRONISTA" => 'EL CRONISTA',
        "FORTUNA" => 'FORTUNA PERFIL',
        "BAENEGOCIOS" => "BAE NEGOCIOS",
        "LAPOLITICAONLINE" => "LA POLÍTICA ONLINE",
        "EDITORIALRN" => "EDITORIAL RN",
        "MASE" => "MÁS ENERGÍA",
        "MINERIAYDESARROLLO" => "MINERÍA Y DESARROLLO",
        "NUEVARIOJA" => "NUEVA RIOJA",
        "FORBESARGENTINA" => "FORBES ARGENTINA",
        "URGENTE24" => "URGENTE 24",
        "MDZOL" => "MDZ",
        "BLOOMBERGLINEA" => "BLOOMBERG LÍNEA",
        "ELDIARIODEVIAJE" => "EL DIARIO DE VIAJE",
        "NOTICIASARGENTINAS" => "NOTICIAS ARGENTINAS",
        "VIAPAIS" => "VÍA PAÍS",
        "CANAL26" => "CANAL 26",
        "ZONA-MILITAR" => "ZONA MILITAR",
    ];

    return $reemplazos[$sitio] ?? $sitio;
}

function limpiarTexto($texto) {
    return trim(preg_replace('/\s+/', ' ', $texto));
}

function obtenerBajada($xpath, $sitio, $reglas) {
    if (isset($reglas[$sitio])) {
        $nodes = $xpath->query($reglas[$sitio]);
        if ($nodes && $nodes->length > 0) {
            return limpiarTexto($nodes->item(0)->textContent);
        }
    }

    $parrafos = $xpath->query("//p");
    return $parrafos->length ? limpiarTexto($parrafos->item(0)->textContent) : '';
}

$reglasBajadaPorMedio = [
    'PÁGINA 12' => '//h3',
    'AMBITO'    => "//*[contains(@class,'ignore-parser')]",
    'ÁMBITO'    => "//*[contains(@class,'ignore-parser')]",
    'INFOBAE' => '//h2',
    'PERFIL' => "//h2[contains(@class,'article__headline')]",
    'LA NACIÓN' => "//h2[contains(@class,'--bajada')]",
    'EL CRONISTA' => "//h2[contains(@class,'article-head__subtitle')]",
    'IPROFESIONAL' => "//div[contains(@class,'epigraph')]",
    'CLARIN' => "//h2[contains(@class,'storySummary')]//li",
    'FORTUNA PERFIL' => "//h2[contains(@class,'news__headline')]",
    "FORBES ARGENTINA" => "//h2[contains(@class,'subtitulo')]",
    "URGENTE 24" => "//*[contains(@class,'ignore-parser')]",
    "MDZ" => "//*[contains(@class,'ignore-parser')]",
    "MÁS ENERGÍA" => "//*[contains(@class,'ignore-parser')]",
    'EL DIARIO DE VIAJE' => '(//p)[2]',
    "NOTICIAS ARGENTINAS" => '//h2[contains(@class,"article-deck")]',
    "CANAL 26" => '//h2[contains(@class,"article-body__subheadline")]',
    "VÍA PAÍS" => '//h2',
    "PÁGINA 12" => '//h2',
];


?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Clipping automático</title>

<style>
body {
    font-family: Inter, system-ui, -apple-system, sans-serif;
    background: #f5f7fa;
    color: #1f2937;
    margin: 0;
    padding: 40px;
}

.container {
    max-width: 980px;
    margin: auto;
    background: #ffffff;
    border-radius: 14px;
    padding: 32px;
    box-shadow: 0 10px 30px rgba(0,0,0,.06);
}

h1 {
    font-size: 22px;
    margin-bottom: 6px;
}

.subtitle {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 24px;
}

textarea {
    width: 100%;
    min-height: 120px;
    padding: 14px;
    font-size: 14px;
    border-radius: 10px;
    border: 1px solid #d1d5db;
    resize: vertical;
}

button {
    background: #111827;
    color: #fff;
    border: none;
    padding: 12px 22px;
    border-radius: 10px;
    font-size: 14px;
    cursor: pointer;
    margin-bottom: 32px;
}

button:hover {
    background: #000;
}

.informe-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nota {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14.6667px;
}

.nota:last-child {
    border-bottom: none;
}


.medio {
    font-weight: bolder;
}

.bajada {
    line-height: 1.5;
}

.actions {
    margin-top: 30px;
    text-align: right;
}
</style>
</head>

<body>

<div class="container">

<h1>Generador de Clipping</h1>
<div class="subtitle">Pegá las URLs y generá el informe automáticamente</div>

<form method="post">
    <textarea name="urls" placeholder="Una URL por línea..."></textarea><br><br>
    <button type="submit">Generar informe</button>
</form>

<?php if (!empty($_POST['urls'])): ?>

<div class="informe-header">
    <img src="https://prgn.com/wp-content/uploads/2022/11/LOGOIdentia_RojoyGrisFULL_cut-300x136.png" style="width:229px;">
</div>

<div id="informe">
<br>
<?php
$urls = explode("\n", $_POST['urls']);
foreach ($urls as $url) {
    $url = trim($url);
    if (!$url) continue;

    $html = @file_get_contents($url);
    $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'auto');

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $sitio = obtenerDominio($url);

    $tituloNode = $xpath->query('//h1')->item(0)
        ?? $dom->getElementsByTagName('title')->item(0);

    $titulo = $tituloNode ? limpiarTexto($tituloNode->textContent) : 'Sin título';
    $bajada = obtenerBajada($xpath, $sitio, $reglasBajadaPorMedio);

    echo "
    <div class='nota'>
        <div class='medio'>{$sitio} " . date('d-m-y') . "</div>
        <div class='titulo'><a href='{$url}' target='_blank'>{$titulo}</a></div>
        <div class='bajada'>{$bajada}</div>
        <br>
    </div>";
}
?>
</div>

<div class="actions">
    <button onclick="copiarInformePremium()">Copiar informe</button>
</div>

<?php endif; ?>

</div>

<script>
async function copiarInformePremium() {
    const informe = document.getElementById('informe');
    await navigator.clipboard.write([
        new ClipboardItem({
            'text/html': new Blob([informe.innerHTML], { type: 'text/html' }),
            'text/plain': new Blob([informe.innerText], { type: 'text/plain' })
        })
    ]);
    alert('Informe copiado con formato');
}
</script>

</body>
</html>
