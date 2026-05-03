<?php

namespace App\Exports;

use App\Models\Transaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    public function collection()
    {
        return Transaction::with(['student.class', 'details.unit.item'])
            ->orderBy('borrow_time', 'desc')
            ->get()
            ->map(function (Transaction $tx) {
                $borrowTime  = $tx->borrow_time;
                $returnTime  = $tx->return_time;

                // Durasi pinjam
                $durasi = '-';
                if ($borrowTime && $returnTime) {
                    $diffMins = (int) $borrowTime->diffInMinutes($returnTime);
                    $jam      = intdiv($diffMins, 60);
                    $menit    = $diffMins % 60;
                    $durasi   = $jam > 0 ? "{$jam} jam {$menit} menit" : "{$menit} menit";
                }

                // Terlambat: tanggal kembali berbeda dengan tanggal pinjam
                $terlambat = '-';
                if ($borrowTime && $returnTime) {
                    $terlambat = $borrowTime->toDateString() !== $returnTime->toDateString()
                        ? 'YA' : 'Tidak';
                } elseif ($borrowTime && !$returnTime) {
                    // Masih dipinjam — cek apakah sudah lewat due_time
                    $terlambat = $tx->due_time && now()->gt($tx->due_time) ? 'BELUM KEMBALI (TERLAMBAT)' : 'Belum dikembalikan';
                }

                $barang = $tx->details
                    ->map(fn ($d) => $d->unit?->item?->name ?? '-')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                return [
                    'id'          => $tx->id,
                    'student'     => $tx->student?->name ?? '-',
                    'nis'         => $tx->student?->nis ?? '-',
                    'class'       => ($tx->student?->class?->class ?? '') . ' ' . ($tx->student?->class?->major ?? ''),
                    'barang'      => $barang ?: '-',
                    'borrow_time' => $borrowTime?->format('d/m/Y H:i') ?? '-',
                    'return_time' => $returnTime?->format('d/m/Y H:i') ?? '-',
                    'durasi'      => $durasi,
                    'status'      => $tx->status === 'active' ? 'Dipinjam' : 'Dikembalikan',
                    'terlambat'   => $terlambat,
                    'notes'       => $tx->notes ?? '-',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Siswa',
            'NIS',
            'Kelas',
            'Barang',
            'Waktu Pinjam',
            'Waktu Kembali',
            'Durasi',
            'Status',
            'Terlambat',
            'Catatan',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 22,
            'C' => 14,
            'D' => 18,
            'E' => 30,
            'F' => 18,
            'G' => 18,
            'H' => 16,
            'I' => 14,
            'J' => 28,
            'K' => 20,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font'      => ['bold' => true],
                'fill'      => [
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }
}
