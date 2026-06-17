<?php

namespace App\Support;

use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Str;

/**
 * Helper que genera adjuntos PDF para campañas de descuento.
 * Construye un documento imprimible con la información del código,
 * valor, vigencia y términos y condiciones asociados.
 * Es llamado desde campañas de notificaciones internas que requieren adjuntar
 * el detalle del descuento en un archivo PDF.
 */
class DiscountPdfAttachmentHelper
{
    /**
     * Genera un adjunto PDF en memoria para campañas de descuento.
     * Retorna un array con content (binario), filename y mime type,
     * o null si la generación falla.
     */
    public static function build(array $discountCode): ?array
    {
        try {
            // Configura opciones de Dompdf: fuente Unicode y deshabilita recursos remotos.
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);

            // Genera el PDF a partir del HTML construido con los datos del descuento.
            $pdf = new Dompdf($options);
            $pdf->loadHtml(self::buildHtml($discountCode), 'UTF-8');
            $pdf->setPaper('letter');
            $pdf->render();

            // Genera un nombre de archivo seguro a partir del código de descuento.
            $code = trim((string) ($discountCode['code'] ?? 'PROMO'));
            $filenameToken = preg_replace('/[^A-Za-z0-9_-]/', '', Str::upper($code)) ?: 'PROMO';

            return [
                'content' => $pdf->output(),                              // Contenido binario del PDF
                'filename' => 'codigo_descuento_' . $filenameToken . '.pdf', // Nombre amigable para el adjunto
                'mime' => 'application/pdf',                               // Tipo MIME estándar
            ];
        } catch (\Throwable) {
            // Si falla la generación del PDF, retorna null sin interrumpir el flujo.
            return null;
        }
    }

    /**
     * Construye el HTML del PDF con la información del descuento,
     * incluyendo código, valor, fechas, usos máximos y términos.
     */
    private static function buildHtml(array $discountCode): string
    {
        // Escapa y formatea cada campo para prevenir inyección HTML en el PDF.
        $code = self::escape($discountCode['code'] ?? 'PROMO');
        $discountValue = self::escape(self::formatDiscountValue($discountCode));
        $startDate = self::escape(self::formatDate($discountCode['start_date'] ?? null, true));
        $endDate = self::escape(self::formatDate($discountCode['end_date'] ?? null, true));
        $maxUses = (int) ($discountCode['max_uses'] ?? 0);
        $maxUsesLabel = $maxUses > 0 ? (string) $maxUses : 'Ilimitados'; // Si es 0, muestra "Ilimitados"
        $singleUseLabel = !empty($discountCode['is_single_use']) ? 'Sí' : 'No';

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #333333; font-size: 12px; }
        .header { border-bottom: 2px solid #006699; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #006699; margin: 0; font-size: 20px; }
        .meta { margin-top: 6px; color: #666666; font-size: 11px; }
        .code-block { text-align: center; margin: 24px 0; }
        .code { font-size: 28px; letter-spacing: 4px; color: #006699; font-weight: 700; }
        .value { font-size: 18px; color: #ff6600; font-weight: 700; margin-top: 8px; }
        .section-title { margin-top: 18px; color: #006699; font-size: 14px; font-weight: 700; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table td { border: 1px solid #dddddd; padding: 8px; }
        .table td:first-child { width: 40%; font-weight: 700; background: #f7f7f7; }
        .terms { margin-top: 18px; font-size: 10px; color: #555555; line-height: 1.5; }
        .footer { margin-top: 22px; border-top: 1px solid #cccccc; padding-top: 8px; text-align: center; font-size: 10px; color: #666666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CÓDIGO DE DESCUENTO</h1>
        <div class="meta">Documento generado para campaña de clientes de Angelow.</div>
    </div>

    <div class="code-block">
        <div class="code">{$code}</div>
        <div class="value">{$discountValue}</div>
    </div>

    <div class="section-title">Información del descuento</div>
    <table class="table">
        <tr>
            <td>Código</td>
            <td>{$code}</td>
        </tr>
        <tr>
            <td>Valor</td>
            <td>{$discountValue}</td>
        </tr>
        <tr>
            <td>Válido desde</td>
            <td>{$startDate}</td>
        </tr>
        <tr>
            <td>Válido hasta</td>
            <td>{$endDate}</td>
        </tr>
        <tr>
            <td>Usos máximos</td>
            <td>{$maxUsesLabel}</td>
        </tr>
        <tr>
            <td>Uso único por cliente</td>
            <td>{$singleUseLabel}</td>
        </tr>
    </table>

    <div class="section-title">Términos y condiciones</div>
    <div class="terms">
        <p>1. Este código no es acumulable con otras promociones.</p>
        <p>2. El descuento aplica sobre el valor de los productos, antes de envío e impuestos.</p>
        <p>3. Angelow puede modificar o cancelar la promoción cuando sea necesario.</p>
        <p>4. El uso del código implica aceptación de los términos comerciales vigentes.</p>
    </div>

    <div class="footer">
        Angelow Ropa Infantil - Documento generado automáticamente.
    </div>
</body>
</html>
HTML;
    }

    /**
     * Formatea el valor del descuento para el PDF.
     * Ejemplos: "$10.000 de descuento", "15% de descuento".
     */
    private static function formatDiscountValue(array $discountCode): string
    {
        $value = (float) ($discountCode['discount_value'] ?? $discountCode['value'] ?? 0);
        $type = Str::lower((string) ($discountCode['type'] ?? 'percent'));

        // Descuento fijo: muestra como "$10.000 de descuento" con separador de miles.
        if ($type === 'fixed') {
            return '$' . number_format($value, 0, ',', '.') . ' de descuento';
        }

        // Descuento porcentual: elimina decimales innecesarios (15.00% -> 15%).
        $normalized = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
        return $normalized . '% de descuento';
    }

    /**
     * Formatea una fecha para mostrarla en el PDF.
     * Si $withTime es true, incluye hora; si no hay fecha, retorna texto por defecto.
     */
    private static function formatDate(mixed $value, bool $withTime = false): string
    {
        // Sin fecha disponible: retorna texto por defecto según si espera hora o no.
        if ($value === null || $value === '') {
            return $withTime ? 'Sin fecha de expiración' : 'Sin fecha';
        }

        try {
            return Carbon::parse($value)->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Throwable) {
            // Si la fecha no es parseable, retorna texto genérico.
            return $withTime ? 'Sin fecha de expiración' : 'Sin fecha';
        }
    }

    /**
     * Escapa caracteres HTML para evitar inyección en el contenido del PDF.
     */
    private static function escape(mixed $value): string
    {
        // Convierte caracteres especiales HTML a entidades para evitar inyección en el PDF.
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
