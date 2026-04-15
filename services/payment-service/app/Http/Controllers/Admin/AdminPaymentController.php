<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminPaymentController extends Controller
{
    /**
     * Lista todas las transacciones con filtros para el admin.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DB::table('payment_transactions')->orderByDesc('created_at');

        if ($request->filled('order_id')) {
            $query->where('order_id', $request->integer('order_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->input('to') . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('user_id', 'like', "%{$search}%");
                if (Schema::hasColumn('payment_transactions', 'billing_email')) {
                    $q->orWhere('billing_email', 'like', "%{$search}%");
                }
            });
        }

        $payments = $query->paginate($request->integer('per_page', 50));
        $paginationMeta = [
            'current_page' => $payments->currentPage(),
            'last_page'    => $payments->lastPage(),
            'total'        => $payments->total(),
        ];

        $paymentRows = collect($payments->items())
            ->map(fn ($payment) => $this->transformPaymentRow($payment))
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $paymentRows,
            'meta'    => $paginationMeta,
        ]);
    }

    private function transformPaymentRow(object $payment): array
    {
        $proofPath = trim((string) ($payment->payment_proof ?? ''));
        $resolvedProofPath = $this->resolveExistingProofPath($proofPath);
        $proofUrl = $this->buildPublicProofUrl($resolvedProofPath ?: $proofPath);

        return [
            ...(array) $payment,
            'proof_name' => $proofPath !== '' ? basename(str_replace('\\', '/', $resolvedProofPath ?: $proofPath)) : null,
            'proof_url' => $proofUrl,
            'proof_exists' => $resolvedProofPath !== null,
        ];
    }

    private function buildPublicProofUrl(string $proofPath): ?string
    {
        if ($proofPath === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $proofPath);

        if (preg_match('/^https?:\/\//i', $normalized)) {
            return $normalized;
        }

        if (str_starts_with($normalized, '/uploads/')) {
            return $normalized;
        }

        if (str_starts_with($normalized, 'uploads/')) {
            return '/' . ltrim($normalized, '/');
        }

        if (str_starts_with($normalized, 'payment_proofs/')) {
            return '/uploads/' . ltrim($normalized, '/');
        }

        return '/uploads/payment_proofs/' . ltrim($normalized, '/');
    }

    private function resolveExistingProofPath(string $proofPath): ?string
    {
        if ($proofPath === '') {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $proofPath), '/');

        if (preg_match('/^https?:\/\//i', $normalized)) {
            return $normalized;
        }

        $candidates = $this->buildProofCandidates($normalized);

        foreach ($candidates as $candidate) {
            if (is_file(public_path($candidate))) {
                return $candidate;
            }
        }

        $basename = basename($normalized);

        if ($basename === '') {
            return null;
        }

        foreach ([public_path('uploads/payment_proofs'), public_path('uploads')] as $directory) {
            $found = $this->findProofByBasename($directory, $basename);

            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    private function buildProofCandidates(string $normalized): array
    {
        $candidates = [];

        if (str_starts_with($normalized, 'uploads/')) {
            $candidates[] = $normalized;
        } elseif (str_starts_with($normalized, 'payment_proofs/')) {
            $candidates[] = 'uploads/' . $normalized;
        } else {
            $candidates[] = 'uploads/payment_proofs/' . $normalized;
            $candidates[] = 'uploads/' . $normalized;
        }

        return array_values(array_unique($candidates));
    }

    private function findProofByBasename(string $directory, string $basename): ?string
    {
        if (!is_dir($directory)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || strcasecmp($file->getFilename(), $basename) !== 0) {
                continue;
            }

            return $this->toRelativePublicPath($file->getPathname());
        }

        return null;
    }

    private function toRelativePublicPath(string $absolutePath): string
    {
        $publicPath = str_replace('\\', '/', public_path());
        $normalized = str_replace('\\', '/', $absolutePath);

        return ltrim(str_replace($publicPath, '', $normalized), '/');
    }

    /**
     * Verifica/actualiza el estado de una transaccion.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status'      => ['required', 'in:approved,rejected,pending'],
            'admin_notes' => ['nullable', 'string'],
        ]);

        $admin = $request->input('_admin_user', []);

        $updated = DB::table('payment_transactions')
            ->where('id', $id)
            ->update([
                'status'      => $data['status'],
                'admin_notes' => $data['admin_notes'] ?? null,
                'verified_by' => $admin['id'] ?? null,
                'verified_at' => now(),
                'updated_at'  => now(),
            ]);

        if (!$updated) {
            return response()->json(['success' => false, 'message' => 'Transaccion no encontrada.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Transaccion actualizada.']);
    }
}
