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
    protected string $reportType;

    public function __construct(array $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function sheets(): array
    {
        return match($this->reportType) {
            'meeting'       => [new MeetingContributionsSheet($this->data), new GeneralSummarySheet($this->data)],
            'member'        => [new MemberReportSheet($this->data)],
            'loans_pending',
            'loans_paid'    => [new LoansReportSheet($this->data)],
            'monthly'       => [new MonthlyReportSheet($this->data)],
            'group'         => [new GroupSummarySheet($this->data)],
            default         => [],
        };
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
                    $meeting->meeting_date?->format('d/m/Y') ?? '',
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


class MemberReportSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function title(): string { return 'Historial de Miembros'; }

    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'ESTADO', 'FECHA INGRESO', 'TOTAL AHORROS', 'TOTAL F. EMERGENCIA', 'PRÉSTAMOS', 'MULTAS'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $members = $this->data['members'] ?? collect();

        foreach ($members as $member) {
            $rows->push([
                $member->full_name,
                $member->group->name ?? '',
                $member->status === 'active' ? 'Activo' : 'Inactivo',
                $member->join_date?->format('d/m/Y') ?? '',
                $member->total_savings,
                $member->total_emergency,
                $member->loans->count(),
                $member->fines->count(),
            ]);
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF6A1B9A']],
            ],
        ];
    }
}


class LoansReportSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function title(): string
    {
        return ($this->data['status'] ?? '') === 'paid' ? 'Préstamos Pagados' : 'Préstamos Pendientes';
    }

    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'MONTO', 'INTERÉS', 'TOTAL A RETORNAR', 'SALDO', 'ESTADO', 'FECHA'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loans = $this->data['loans'] ?? collect();

        foreach ($loans as $loan) {
            $rows->push([
                $loan->member->full_name ?? '',
                $loan->group->name ?? '',
                $loan->amount,
                $loan->interest_amount,
                $loan->total_to_return,
                $loan->balance,
                $loan->status,
                $loan->created_at?->format('d/m/Y') ?? '',
            ]);
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFB71C1C']],
            ],
        ];
    }
}


class MonthlyReportSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function title(): string { return 'Aportes Mensuales'; }

    public function headings(): array
    {
        return ['GRUPO', 'REUNIÓN', 'MES', 'MIEMBRO', 'AHORRO', 'F. EMERGENCIA', 'MULTA', 'TOTAL', 'CONFIRMADO'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $contributions = $this->data['contributions'] ?? collect();

        foreach ($contributions as $contribution) {
            $rows->push([
                $contribution->meeting->group->name ?? '',
                $contribution->meeting->meeting_number ?? '',
                $contribution->meeting->month ?? '',
                $contribution->member->full_name ?? '',
                $contribution->savings,
                $contribution->emergency_fund,
                $contribution->fine,
                $contribution->total,
                $contribution->confirmed ? 'Sí' : 'No',
            ]);
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFE65100']],
            ],
        ];
    }
}


class GroupSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;

    public function __construct(array $data) { $this->data = $data; }

    public function title(): string { return 'Resumen de Grupos'; }

    public function headings(): array
    {
        return ['GRUPO', 'ESTADO', 'FECHA INICIO', 'MIEMBROS', 'REUNIONES', 'VALOR ACCIÓN'];
    }

    public function collection(): Collection
    {
        $rows = collect();
        $groups = $this->data['groups'] ?? collect();

        foreach ($groups as $group) {
            $rows->push([
                $group->name,
                $group->status === 'active' ? 'Activo' : 'Inactivo',
                $group->start_date?->format('d/m/Y') ?? '',
                $group->members->count(),
                $group->meetings->count(),
                $group->share_value,
            ]);
        }
        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF0277BD']],
            ],
        ];
    }
}
