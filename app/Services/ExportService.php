<?php

namespace App\Services;

use App\Exports\RekapExport;
use App\Exports\TransactionsExport;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportService
{
    public function exportTransactions(): BinaryFileResponse
    {
        return Excel::download(new TransactionsExport(), 'transactions.xlsx');
    }

    public function exportRekap(): BinaryFileResponse
    {
        return Excel::download(new RekapExport(), 'rekap-transaksi.xlsx');
    }
}
