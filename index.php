<?php
\error_reporting(-1);
if ((!empty($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] === '127.0.0.1') || PHP_SAPI === 'cli') {
    \ini_set('display_errors', '1');
} else {
    \ini_set('display_errors', '0');
}
$documentRoot = $documentRoot ?? (PHP_SAPI !== 'cli' ? \rtrim(\dirname($_SERVER['SCRIPT_FILENAME']), '\/') : \getcwd());
$vendorRoot = $vendorRoot ?? ($documentRoot . '/vendor');

/** @noinspection PhpIncludeInspection */
require_once $vendorRoot . '/autoload.php';

$htmlHelper = $htmlHelper ?? \DrdPlus\FrontendSkeleton\HtmlHelper::createFromGlobals($documentRoot);
\DrdPlus\FrontendSkeleton\TracyDebugger::enable($htmlHelper->isInProduction());

$controller = $controller ?? new \DrdPlus\FrontendSkeleton\FrontendController(
        $htmlHelper,
        $documentRoot,
        null, // automatic web root
        $vendorRoot,
        $partsRoot ?? null,
        $genericPartsRoot ?? null
    );
$vendorRoot = $controller->getVendorRoot();
$partsRoot = $controller->getPartsRoot();
$genericPartsRoot = $controller->getGenericPartsRoot();

/** @noinspection PhpIncludeInspection */
echo require $genericPartsRoot . '/content.php';