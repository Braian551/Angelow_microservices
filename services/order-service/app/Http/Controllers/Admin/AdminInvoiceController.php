<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminInvoiceController extends Controller
{
    private const LEGACY_CONNECTION = 'legacy_mysql';

    public function __construct(
        private readonly OrderInvoiceService $orderInvoiceService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:20'],
            'payment_status' => ['nullable', 'string', 'max:20'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'source' => ['nullable', 'in:legacy,microservice'],
        ]);

        $rows = $this->orderInvoiceService->listGeneratedInvoices($data, (int) ($data['limit'] ?? 200));

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
                'stats' => $this->buildStats($rows),
            ],
        ]);
    }

    public function download(Request $request, int $id)
    {
        $data = $request->validate([
            'source' => ['nullable', 'in:legacy,microservice'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($data['source'] ?? null);
        $result = $this->orderInvoiceService->buildInvoicePdfForDownload($id, $preferredConnection);

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'No se pudo descargar la factura.',
            ], (int) ($result['code'] ?? 422));
        }

        return response($result['content'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . ($result['filename'] ?? ('factura_' . $id . '.pdf')) . '"',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    public function resend(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'source' => ['nullable', 'in:legacy,microservice'],
        ]);

        $preferredConnection = $this->normalizeSourceConnection($data['source'] ?? null);
        $result = $this->orderInvoiceService->resendInvoiceEmail($id, $preferredConnection);

        if (!($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'No se pudo reenviar la factura.',
            ], (int) ($result['code'] ?? 422));
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Factura reenviada.',
            'data' => $result,
        ]);
    }

    private function buildStats(Collection $rows): array
    {
        $totalInvoices = $rows->count();
        $totalAmount = $rows->sum(static fn (array $row): float => (float) ($row['total'] ?? 0));
        $paidInvoices = $rows->filter(function (array $row): bool {
            $payment = strtolower(trim((string) ($row['payment_status'] ?? '')));
            return in_array($payment, ['paid', 'verified', 'approved'], true);
        })->count();

        $deliveredInvoices = $rows->filter(function (array $row): bool {
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            return in_array($status, ['delivered', 'completed'], true);
        })->count();

        $uniqueCustomers = $rows
            ->map(static function (array $row): string {
                $email = strtolower(trim((string) ($row['customer_email'] ?? '')));
                $userId = trim((string) ($row['user_id'] ?? ''));
                if ($userId !== '') {
                    return 'id:' . $userId;
                }

                if ($email !== '') {
                    return 'email:' . $email;
                }

                return 'guest:' . (string) ($row['id'] ?? '0');
            })
            ->unique()
            ->count();

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => round((float) $totalAmount, 2),
            'paid_invoices' => $paidInvoices,
            'delivered_invoices' => $deliveredInvoices,
            'unique_customers' => $uniqueCustomers,
        ];
    }

    private function normalizeSourceConnection(?string $source): ?string
    {
        $value = strtolower(trim((string) ($source ?? '')));

        if ($value === 'legacy') {
            return self::LEGACY_CONNECTION;
        }

        return null;
    }
}
