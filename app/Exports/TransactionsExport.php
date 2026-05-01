<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TransactionsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Transaction::with(['student.class', 'details.unit'])
            ->get()
            ->map(function (Transaction $transaction) {
                $qrCodes = $transaction->details
                    ->pluck('unit.qr_code')
                    ->filter()
                    ->implode(', ');

                return [
                    'id'          => $transaction->id,
                    'student'     => $transaction->student?->name,
                    'nis'         => $transaction->student?->nis,
                    'class'       => $transaction->student?->class?->class . ' ' . $transaction->student?->class?->major,
                    'units'       => $qrCodes,
                    'borrow_time' => $transaction->borrow_time?->format('Y-m-d H:i:s'),
                    'due_time'    => $transaction->due_time?->format('Y-m-d H:i:s'),
                    'return_time' => $transaction->return_time?->format('Y-m-d H:i:s'),
                    'status'      => $transaction->status,
                    'notes'       => $transaction->notes,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID Transaksi',
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Unit (QR Code)',
            'Waktu Peminjaman',
            'Batas Pengembalian',
            'Waktu Pengembalian',
            'Status',
            'Catatan',
        ];
    }
}
