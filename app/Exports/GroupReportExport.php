<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class GroupReportExport implements WithMultipleSheets
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function sheets(): array
    {
        return [
            new MeetingContributionsSheet($this->data),
            new GeneralSummarySheet($this->data),
        ];
    }
}


class MeetingContributionsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function title(): string { return 'Aportes por Reunión'; }

    public function headings(): array
    {
        return ['REUNIÓN N°', 'FECHA', 'MES', 'MIEMBRO', 'AHORRO', 'F. EMERGENCIA', 'MULTA', 'TOTAL', 'CONFIRMADO'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $meetings = $this->data['meetings'] ?? collect();

        foreach ($meetings as $meeting) {
            foreach ($meeting->contributions as $contribution) {
                $rows->push([
                    $meeting->meeting_number,
                    $meeting->meeting_date->format('d/m/Y'),
                    $meeting->month,
                    $contribution->member->full_name,
                    $contribution->savings,
                    $contribution->emergency_fund,
                    $contribution->fine,
                    $contribution->total,
                    $contribution->confirmed ? 'Sí' : 'No',
                ]);
            }
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2E7D32']],
            ],
        ];
    }
}


class GeneralSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function title(): string { return 'Resumen General'; }

    public function headings(): array
    {
        return [
            'REUNIÓN N°', 'TOTAL ANTERIOR', 'INGRESO Y AHORROS', 'EGRESO PRÉSTAMO',
            'TOTAL FONDOS GRUPO', 'TOTAL F. EMERGENCIA', 'INGRESO MULTAS',
            'INGRESO INTERÉS', 'GASTOS BANCARIOS',
        ];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $meetings = $this->data['meetings'] ?? collect();

        foreach ($meetings as $meeting) {
            if ($meeting->summary) {
                $rows->push([
                    $meeting->meeting_number,
                    $meeting->summary->previous_total,
                    $meeting->summary->income_savings,
                    $meeting->summary->loan_outflow,
                    $meeting->summary->total_group_funds,
                    $meeting->summary->total_emergency_funds,
                    $meeting->summary->income_fines,
                    $meeting->summary->income_interest,
                    $meeting->summary->bank_expenses_total,
                ]);
            }
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1565C0']],
            ],
        ];
    }
}
