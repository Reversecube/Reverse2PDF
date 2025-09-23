<?php
/**
 * Reverse2PDF Vendor Autoloader
 * Simple autoloader for PDF libraries
 */

// Define vendor directory
define('REVERSE2PDF_VENDOR_DIR', __DIR__);

// DomPDF Autoloader
if (!class_exists('\Dompdf\Dompdf')) {
    // Check if DomPDF is available via Composer
    if (file_exists(REVERSE2PDF_VENDOR_DIR . '/dompdf/dompdf/autoload.inc.php')) {
        require_once REVERSE2PDF_VENDOR_DIR . '/dompdf/dompdf/autoload.inc.php';
    } else {
        // Fallback: Basic PDF class for when DomPDF is not available
        require_once __DIR__ . '/fallback-pdf.php';
    }
}

// TCPDF Autoloader
if (!class_exists('TCPDF')) {
    if (file_exists(REVERSE2PDF_VENDOR_DIR . '/tcpdf/tcpdf.php')) {
        require_once REVERSE2PDF_VENDOR_DIR . '/tcpdf/tcpdf.php';
    }
}

// mPDF Autoloader
if (!class_exists('\Mpdf\Mpdf')) {
    if (file_exists(REVERSE2PDF_VENDOR_DIR . '/mpdf/mpdf/autoload.php')) {
        require_once REVERSE2PDF_VENDOR_DIR . '/mpdf/mpdf/autoload.php';
    }
}
?>
