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
        $this->data       = $data;
        $this->reportType = $reportType;
    }

    public function sheets(): array
    {
        return match ($this->reportType) {
            'meeting'              => [new MeetingContributionsSheet($this->data), new GeneralSummarySheet($this->data)],
            'member'               => [new MemberReportSheet($this->data)],
            'loans_pending',
            'loans_paid'           => [new LoansReportSheet($this->data)],
            'monthly'              => [new MonthlyReportSheet($this->data)],
            'group'                => [new GroupSummarySheet($this->data)],
            'financial_summary'    => [new FinancialSummarySheet($this->data)],
            'savings_evolution'    => [new SavingsEvolutionSheet($this->data)],
            'cash_statement'       => [new CashStatementSheet($this->data)],
            'member_contributions' => [new MemberContributionsSheet($this->data)],
            'member_ranking'       => [new MemberRankingSaversSheet($this->data), new MemberRankingPunctualSheet($this->data)],
            'delinquent_members'   => [new DelinquentMembersSheet($this->data)],
            'loan_history'         => [new LoanHistorySheet($this->data)],
            'active_loans'         => [new ActiveLoansSheet($this->data)],
            'loan_recovery'        => [new LoanRecoverySheet($this->data)],
            'loan_profitability'   => [new LoanProfitabilitySheet($this->data)],
            'meeting_attendance'   => [new MeetingAttendanceSheet($this->data)],
            'member_participation' => [new MemberParticipationSheet($this->data)],
            'fines_generated'      => [new FinesGeneratedSheet($this->data)],
            'fines_status'         => [new FinesStatusSheet($this->data)],
            'comparative_groups'   => [new ComparativeGroupsSheet($this->data)],
            'comparative_periods'  => [new ComparativePeriodsSheet($this->data)],
            default                => [],
        };
    }
}


// ──────────────────────────────────────────────────────────────
// Original sheets
// ──────────────────────────────────────────────────────────────

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
        foreach ($this->data['meetings'] ?? collect() as $meeting) {
            foreach ($meeting->contributions as $c) {
                $rows->push([
                    $meeting->meeting_number,
                    $meeting->meeting_date?->format('d/m/Y') ?? '',
                    $meeting->month,
                    $c->member->full_name,
                    $c->savings, $c->emergency_fund, $c->fine, $c->total,
                    $c->confirmed ? 'Sí' : 'No',
                ]);
            }
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2E7D32']]]];
    }
}


class GeneralSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Resumen General'; }
    public function headings(): array
    {
        return ['REUNIÓN N°', 'TOTAL ANTERIOR', 'INGRESO Y AHORROS', 'EGRESO PRÉSTAMO', 'TOTAL FONDOS GRUPO', 'TOTAL F. EMERGENCIA', 'INGRESO MULTAS', 'INGRESO INTERÉS', 'GASTOS BANCARIOS'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['meetings'] ?? collect() as $meeting) {
            if ($meeting->summary) {
                $s = $meeting->summary;
                $rows->push([$meeting->meeting_number, $s->previous_total, $s->income_savings, $s->loan_outflow, $s->total_group_funds, $s->total_emergency_funds, $s->income_fines, $s->income_interest, $s->bank_expenses_total]);
            }
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1565C0']]]];
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
        foreach ($this->data['members'] ?? collect() as $m) {
            $rows->push([$m->full_name, $m->group->name ?? '', $m->status === 'active' ? 'Activo' : 'Inactivo', $m->join_date?->format('d/m/Y') ?? '', $m->total_savings, $m->total_emergency, $m->loans->count(), $m->fines->count()]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF6A1B9A']]]];
    }
}


class LoansReportSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return ($this->data['status'] ?? '') === 'paid' ? 'Préstamos Pagados' : 'Préstamos Pendientes'; }
    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'MONTO', 'INTERÉS', 'TOTAL A RETORNAR', 'SALDO', 'ESTADO', 'FECHA'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['loans'] ?? collect() as $l) {
            $rows->push([$l->member->full_name ?? '', $l->group->name ?? '', $l->amount, $l->interest_amount, $l->total_to_return, $l->balance, $l->status, $l->created_at?->format('d/m/Y') ?? '']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFB71C1C']]]];
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
        foreach ($this->data['contributions'] ?? collect() as $c) {
            $rows->push([$c->meeting->group->name ?? '', $c->meeting->meeting_number ?? '', $c->meeting->month ?? '', $c->member->full_name ?? '', $c->savings, $c->emergency_fund, $c->fine, $c->total, $c->confirmed ? 'Sí' : 'No']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFE65100']]]];
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
        foreach ($this->data['groups'] ?? collect() as $g) {
            $rows->push([$g->name, $g->status === 'active' ? 'Activo' : 'Inactivo', $g->start_date?->format('d/m/Y') ?? '', $g->members->count(), $g->meetings->count(), $g->share_value]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF0277BD']]]];
    }
}


// ──────────────────────────────────────────────────────────────
// New sheets — Financial
// ──────────────────────────────────────────────────────────────

class FinancialSummarySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Resumen Financiero'; }
    public function headings(): array
    {
        return ['GRUPO', 'TOTAL AHORRADO', 'F. EMERGENCIA', 'MULTAS COBRADAS', 'PRÉSTAMOS RECUPERADOS', 'INTERESES', 'ACTIVIDADES', 'SUBTOTAL INGRESOS', 'PRÉSTAMOS OTORGADOS', 'GASTOS BANCARIOS', 'SUBTOTAL EGRESOS', 'TOTAL GENERAL', 'SALDO DISPONIBLE'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['summary'] ?? [] as $item) {
            $rows->push([$item['group']->name, $item['total_savings'], $item['total_emergency'], $item['total_fines'], $item['total_loans_recov'], $item['total_interest'], $item['total_activities'] ?? 0, $item['subtotal_income'], $item['total_loans_out'], $item['total_bank_expenses'], $item['subtotal_outflow'], $item['grand_total'], $item['available_balance']]);
        }
        if (!empty($this->data['consolidated'])) {
            $c = $this->data['consolidated'];
            $rows->push(['TOTAL CONSOLIDADO', '', '', '', '', '', '', $c['subtotal_income'], '', '', $c['subtotal_outflow'], $c['grand_total'], $c['available_balance']]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1565C0']]]];
    }
}


class SavingsEvolutionSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Evolución de Ahorros'; }
    public function headings(): array { return ['MES', 'AHORROS DEL MES', 'ACUMULADO']; }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['monthly_data'] ?? [] as $row) {
            $rows->push([$row['label'], $row['savings'], $row['accumulated']]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1565C0']]]];
    }
}


class CashStatementSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Estado de Caja'; }
    public function headings(): array
    {
        return ['REUNIÓN', 'FECHA', 'SALDO ANTERIOR', '+ AHORROS', '+ INTERESES', '+ MULTAS', 'TOTAL INGRESOS', '- PRÉSTAMOS', '- GASTOS BANC.', 'TOTAL EGRESOS', 'SALDO FINAL'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['rows'] ?? [] as $row) {
            $rows->push(['N° ' . $row['meeting']->meeting_number, $row['meeting']->meeting_date?->format('d/m/Y') ?? '', $row['previous'], $row['income_savings'], $row['income_interest'], $row['income_fines'], $row['total_income'], $row['loans_out'], $row['bank_expenses'], $row['total_expenses'], $row['balance']]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF37474F']]]];
    }
}


// ──────────────────────────────────────────────────────────────
// New sheets — Members
// ──────────────────────────────────────────────────────────────

class MemberContributionsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Aportes por Miembro'; }
    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'TOTAL APORTADO', 'CUOTAS PAGADAS', 'CUOTAS PENDIENTES', '% CUMPLIMIENTO'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['rows'] ?? collect() as $row) {
            $rows->push([$row['member']->full_name, $row['member']->group->name ?? '', $row['total_saved'], $row['paid_count'], $row['pending_count'], $row['compliance'] . '%']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF6A1B9A']]]];
    }
}


class MemberRankingSaversSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Top Ahorradores'; }
    public function headings(): array { return ['#', 'MIEMBRO', 'GRUPO', 'TOTAL AHORRADO', 'CUOTAS PAGADAS']; }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['top_savers'] ?? collect() as $i => $row) {
            $rows->push([$i + 1, $row['member']->full_name, $row['member']->group->name ?? '', $row['total_saved'], $row['paid']]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2E7D32']]]];
    }
}


class MemberRankingPunctualSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Top Puntualidad'; }
    public function headings(): array { return ['#', 'MIEMBRO', 'GRUPO', 'ASISTENCIAS', '% PUNTUALIDAD']; }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['top_punctual'] ?? collect() as $i => $row) {
            $rows->push([$i + 1, $row['member']->full_name, $row['member']->group->name ?? '', $row['attended'], $row['punctuality'] . '%']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1565C0']]]];
    }
}


class DelinquentMembersSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Miembros Morosos'; }
    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'MULTAS PENDIENTES', 'MONTO MULTAS', 'PRÉSTAMOS ACTIVOS', 'SALDO PRÉSTAMOS', 'DÍAS ATRASO'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['rows'] ?? collect() as $row) {
            $rows->push([$row['member']->full_name, $row['member']->group->name ?? '', $row['pending_fines']->count(), $row['fines_amount'], $row['active_loans']->count(), $row['loans_balance'], $row['overdue_days']]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFB71C1C']]]];
    }
}


// ──────────────────────────────────────────────────────────────
// New sheets — Loans
// ──────────────────────────────────────────────────────────────

class LoanHistorySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Historial Préstamos'; }
    public function headings(): array
    {
        return ['FECHA', 'MIEMBRO', 'GRUPO', 'MONTO', 'INTERÉS %', 'TOTAL A RETORNAR', 'PAGADO', 'SALDO', 'ESTADO', 'VENCIMIENTO'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['loans'] ?? collect() as $l) {
            $rows->push([$l->delivery_date?->format('d/m/Y') ?? '', $l->member->full_name ?? '', $l->group->name ?? '', $l->amount, $l->interest_rate . '%', $l->total_to_return, $l->amount_paid ?? 0, $l->balance ?? 0, $l->status, $l->due_date?->format('d/m/Y') ?? '']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFE65100']]]];
    }
}


class ActiveLoansSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Préstamos Activos'; }
    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'CAPITAL PENDIENTE', 'TOTAL ORIGINAL', 'CUOTAS PAGADAS', 'VENCIMIENTO', 'DÍAS PARA VENCER', 'ESTADO'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['rows'] ?? collect() as $row) {
            $l = $row['loan'];
            $rows->push([$l->member->full_name ?? '', $l->group->name ?? '', $l->balance ?? 0, $l->total_to_return, $row['installments_paid'], $l->due_date?->format('d/m/Y') ?? '', $row['days_to_due'] ?? '', $l->status]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF4527A0']]]];
    }
}


class LoanRecoverySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Recuperación Préstamos'; }
    public function headings(): array { return ['GRUPO', 'TOTAL PRESTADO', 'TOTAL RECUPERADO', 'PENDIENTE', '% RECUPERADO']; }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['totals'] ?? [] as $item) {
            $pct = $item['loaned'] > 0 ? round(($item['recovered'] / $item['loaned']) * 100, 1) : 0;
            $rows->push([$item['group']->name, $item['loaned'], $item['recovered'], $item['pending'], $pct . '%']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF01579B']]]];
    }
}


class LoanProfitabilitySheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Rentabilidad Préstamos'; }
    public function headings(): array
    {
        return ['FECHA', 'MIEMBRO', 'GRUPO', 'MONTO', 'TASA %', 'INTERÉS GENERADO', 'INTERÉS COBRADO', 'ESTADO'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['loans'] ?? collect() as $l) {
            $rows->push([$l->delivery_date?->format('d/m/Y') ?? '', $l->member->full_name ?? '', $l->group->name ?? '', $l->amount, $l->interest_rate . '%', $l->interest_amount, $l->payments->sum('interest_paid'), $l->status]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF00695C']]]];
    }
}


// ──────────────────────────────────────────────────────────────
// New sheets — Meetings & Fines
// ──────────────────────────────────────────────────────────────

class MeetingAttendanceSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Asistencia Reuniones'; }
    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'ASISTENCIAS', 'AUSENCIAS', '% ASISTENCIA'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['member_rows'] ?? collect() as $row) {
            $rows->push([$row['member']->full_name, $row['member']->group->name ?? '', $row['attended'], $row['absent'], $row['pct'] . '%']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF2E7D32']]]];
    }
}


class MemberParticipationSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Participación Miembros'; }
    public function headings(): array
    {
        return ['MIEMBRO', 'GRUPO', 'ASISTENCIAS', '% ASISTENCIA', 'APORTES', 'TOTAL AHORRADO', 'PRÉSTAMOS SOLICITADOS'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['rows'] ?? collect() as $row) {
            $rows->push([$row['member']->full_name, $row['member']->group->name ?? '', $row['attended'], $row['attendance_pct'] . '%', $row['contributions'], $row['total_saved'], $row['loans_requested']]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF00838F']]]];
    }
}


class FinesGeneratedSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Multas Generadas'; }
    public function headings(): array
    {
        return ['FECHA REUNIÓN', 'MIEMBRO', 'GRUPO', 'MOTIVO', 'MONTO', 'ESTADO'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['fines'] ?? collect() as $fine) {
            $rows->push([$fine->meeting?->meeting_date?->format('d/m/Y') ?? '', $fine->member->full_name ?? '', $fine->member->group->name ?? '', $fine->reason ?? '', $fine->amount, $fine->status === 'paid' ? 'Cobrada' : 'Pendiente']);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFF57F17']]]];
    }
}


class FinesStatusSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Estado de Multas'; }
    public function headings(): array
    {
        return ['ESTADO', 'CANTIDAD', 'TOTAL MONTO'];
    }
    public function collection(): Collection
    {
        $paid    = $this->data['paid']    ?? collect();
        $pending = $this->data['pending'] ?? collect();
        return collect([
            ['Cobradas',   $paid->count(),    $paid->sum('amount')],
            ['Pendientes', $pending->count(),  $pending->sum('amount')],
            ['TOTAL',      $paid->count() + $pending->count(), $paid->sum('amount') + $pending->sum('amount')],
        ]);
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FFB71C1C']]]];
    }
}


// ──────────────────────────────────────────────────────────────
// New sheets — Comparative
// ──────────────────────────────────────────────────────────────

class ComparativeGroupsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Comparativo de Grupos'; }
    public function headings(): array
    {
        return ['GRUPO', 'MIEMBROS ACTIVOS', 'TOTAL AHORRADO', 'F. EMERGENCIA', 'MULTAS COBRADAS', 'PRÉSTAMOS OTORGADOS', 'PRÉSTAMOS RECUPERADOS', 'SALDO PENDIENTE', 'INTERESES COBRADOS', '% ASISTENCIA'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['rows'] ?? [] as $row) {
            $rows->push([
                $row['group']->name,
                $row['active_members'],
                $row['total_savings'],
                $row['total_emergency'],
                $row['total_fines'],
                $row['total_loans_out'],
                $row['total_loans_recov'],
                $row['total_loans_bal'],
                $row['total_interest'],
                $row['attendance_rate'] . '%',
            ]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF6A1B9A']]]];
    }
}


class ComparativePeriodsSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize
{
    protected array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function title(): string { return 'Comparativo de Períodos'; }
    public function headings(): array
    {
        return ['PERÍODO', 'AHORROS', 'MULTAS COBRADAS', 'PRÉSTAMOS OTORGADOS', 'PAGOS DE PRÉSTAMOS', '% ASISTENCIA', 'VARIACIÓN AHORROS'];
    }
    public function collection(): Collection
    {
        $rows = collect();
        foreach ($this->data['periods'] ?? [] as $row) {
            $rows->push([
                $row['label'],
                $row['savings'],
                $row['fines'],
                $row['loans_out'],
                $row['loan_payments'],
                $row['attendance_rate'] . '%',
                $row['savings_delta'] === null ? '—' : $row['savings_delta'] . '%',
            ]);
        }
        return $rows;
    }
    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1565C0']]]];
    }
}
