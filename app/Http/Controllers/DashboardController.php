<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $applyRange = function (Builder $query) use ($from, $to): Builder {
            return $query->whereNull('archived_at')
                ->when($from !== null, fn (Builder $query) => $query->whereDate('created_at', '>=', $from))
                ->when($to !== null, fn (Builder $query) => $query->whereDate('created_at', '<=', $to));
        };

        $statuses = ['open', 'pending', 'in_progress', 'resolved', 'closed'];
        $statusRows = $applyRange(Ticket::query())
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $statusCounts = collect($statuses)->mapWithKeys(
            fn (string $status): array => [$status => (int) ($statusRows[$status] ?? 0)],
        );
        $priorityRows = $applyRange(Ticket::query())
            ->selectRaw('LOWER(priority) as priority, COUNT(*) as total')
            ->groupByRaw('LOWER(priority)')
            ->pluck('total', 'priority');
        $priorityCounts = collect(['low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0]);

        foreach ($priorityRows as $priority => $count) {
            $category = match ($priority) {
                'low' => 'low',
                'normal', 'medium' => 'medium',
                'high' => 'high',
                'urgent', 'critical' => 'critical',
                default => null,
            };

            if ($category !== null) {
                $priorityCounts[$category] += (int) $count;
            }
        }

        $coordinatorRows = $applyRange(Ticket::query())
            ->selectRaw('coordinator_name, COUNT(*) as total')
            ->groupBy('coordinator_name')
            ->orderByDesc('total')
            ->orderBy('coordinator_name')
            ->get();
        $coordinatorPalette = [
            ['#2563EB', '#1D4ED8'],
            ['#16A34A', '#15803D'],
            ['#7C3AED', '#6D28D9'],
            ['#EA580C', '#C2410C'],
            ['#DC2626', '#B91C1C'],
            ['#0891B2', '#0E7490'],
        ];
        $coordinatorChart = $coordinatorRows->take(5)->values()->map(
            fn (object $row, int $index): array => [
                'label' => $row->coordinator_name,
                'count' => (int) $row->total,
                'color' => $coordinatorPalette[$index][0],
                'sideColor' => $coordinatorPalette[$index][1],
            ],
        );
        $otherCoordinatorCount = (int) $coordinatorRows->skip(5)->sum('total');

        if ($otherCoordinatorCount > 0) {
            $coordinatorChart->push([
                'label' => 'Lainnya',
                'count' => $otherCoordinatorCount,
                'color' => $coordinatorPalette[5][0],
                'sideColor' => $coordinatorPalette[5][1],
            ]);
        }

        $priorityChart = $priorityCounts->map(fn (int $count, string $label): array => [
            'label' => ucfirst($label),
            'count' => $count,
        ])->values();
        $priorityAxisMaximum = max(4, (int) (ceil($priorityCounts->max() / 4) * 4));
        $priorityAxis = collect(range(0, 4))->map(fn (int $step): array => [
            'label' => (string) (int) ($priorityAxisMaximum * $step / 4),
            'y' => 160 - (145 * $step / 4),
        ]);
        $priorityBars = $priorityChart->map(fn (array $priority, int $index): array => [
            ...$priority,
            'x' => 63 + ($index * 83),
            'height' => 145 * $priority['count'] / $priorityAxisMaximum,
            'color' => ['Low' => '#16A34A', 'Medium' => '#EAB308', 'High' => '#EA580C', 'Critical' => '#DC2626'][$priority['label']],
        ]);

        return view('dashboard', [
            'from' => $from,
            'to' => $to,
            'totalTickets' => (int) $statusRows->sum(),
            'statusCounts' => $statusCounts,
            'statusChart' => $this->chartSegments(
                $statusCounts->map(fn (int $count, string $status): array => [
                    'label' => ucfirst(str_replace('_', ' ', $status)),
                    'count' => $count,
                    'color' => ['open' => '#64748B', 'pending' => '#D97706', 'in_progress' => '#2563EB', 'resolved' => '#16A34A', 'closed' => '#475569'][$status],
                    'sideColor' => ['open' => '#475569', 'pending' => '#B45309', 'in_progress' => '#1D4ED8', 'resolved' => '#15803D', 'closed' => '#334155'][$status],
                ])->values(),
            ),
            'coordinatorChart' => $this->chartSegments($coordinatorChart, true),
            'priorityCounts' => $priorityCounts,
            'priorityAxis' => $priorityAxis,
            'priorityBars' => $priorityBars,
            'recentTickets' => $applyRange(Ticket::query())
                ->with('engineer:id,name')
                ->latest('created_at')
                ->latest('id')
                ->limit(8)
                ->get(),
            'followUpTickets' => $applyRange(Ticket::query())
                ->with('engineer:id,name')
                ->whereIn('status', ['open', 'pending', 'in_progress'])
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'recentActivities' => TicketActivity::query()->whereHas('ticket')->with(['ticket:id,ticket_number', 'user:id,name'])
                ->latest()->limit(8)->get(),
        ]);
    }

    /**
     * @param  Collection<int, array{label: string, count: int, color: string, sideColor: string}>  $items
     * @return array<int, array{label: string, count: int, color: string, sideColor: string, path: string, labelX: float, labelY: float, showLabel: bool}>
     */
    private function chartSegments(Collection $items, bool $doughnut = false): array
    {
        $total = (int) $items->sum('count');

        if ($total === 0) {
            return [];
        }

        $centerX = 120;
        $centerY = 76;
        $outerRadiusX = 83;
        $outerRadiusY = 56;
        $innerRadiusX = 43;
        $innerRadiusY = 29;
        $startAngle = -90.0;

        return $items->filter(fn (array $item): bool => $item['count'] > 0)
            ->values()
            ->map(function (array $item) use ($total, $centerX, $centerY, $outerRadiusX, $outerRadiusY, $innerRadiusX, $innerRadiusY, &$startAngle, $doughnut): array {
                $sweepAngle = $item['count'] / $total * 360;
                $endAngle = $startAngle + $sweepAngle;
                $start = $this->chartPoint($centerX, $centerY, $outerRadiusX, $outerRadiusY, $startAngle);
                $end = $this->chartPoint($centerX, $centerY, $outerRadiusX, $outerRadiusY, $endAngle);
                $largeArc = $sweepAngle > 180 ? 1 : 0;

                if ($doughnut && $sweepAngle >= 359.99) {
                    $path = sprintf(
                        'M %d %d A %d %d 0 1 1 %d %d A %d %d 0 1 1 %d %d Z M %d %d A %d %d 0 1 0 %d %d A %d %d 0 1 0 %d %d Z',
                        $centerX, $centerY - $outerRadiusY, $outerRadiusX, $outerRadiusY, $centerX, $centerY + $outerRadiusY,
                        $outerRadiusX, $outerRadiusY, $centerX, $centerY - $outerRadiusY,
                        $centerX, $centerY - $innerRadiusY, $innerRadiusX, $innerRadiusY, $centerX, $centerY + $innerRadiusY,
                        $innerRadiusX, $innerRadiusY, $centerX, $centerY - $innerRadiusY,
                    );
                } elseif ($doughnut) {
                    $innerStart = $this->chartPoint($centerX, $centerY, $innerRadiusX, $innerRadiusY, $startAngle);
                    $innerEnd = $this->chartPoint($centerX, $centerY, $innerRadiusX, $innerRadiusY, $endAngle);
                    $path = sprintf(
                        'M %.2f %.2f A %d %d 0 %d 1 %.2f %.2f L %.2f %.2f A %d %d 0 %d 0 %.2f %.2f Z',
                        $start['x'], $start['y'], $outerRadiusX, $outerRadiusY, $largeArc, $end['x'], $end['y'],
                        $innerEnd['x'], $innerEnd['y'], $innerRadiusX, $innerRadiusY, $largeArc, $innerStart['x'], $innerStart['y'],
                    );
                } elseif ($sweepAngle >= 359.99) {
                    $path = sprintf(
                        'M %d %d L %d %d A %d %d 0 1 1 %d %d A %d %d 0 1 1 %d %d Z',
                        $centerX, $centerY, $centerX, $centerY - $outerRadiusY, $outerRadiusX, $outerRadiusY,
                        $centerX, $centerY + $outerRadiusY, $outerRadiusX, $outerRadiusY, $centerX, $centerY - $outerRadiusY,
                    );
                } else {
                    $path = sprintf(
                        'M %d %d L %.2f %.2f A %d %d 0 %d 1 %.2f %.2f Z',
                        $centerX, $centerY, $start['x'], $start['y'], $outerRadiusX, $outerRadiusY, $largeArc, $end['x'], $end['y'],
                    );
                }

                $labelPoint = $this->chartPoint($centerX, $centerY, $doughnut ? 62 : 48, $doughnut ? 43 : 33, $startAngle + ($sweepAngle / 2));
                $startAngle = $endAngle;

                return [
                    ...$item,
                    'path' => $path,
                    'labelX' => $labelPoint['x'],
                    'labelY' => $labelPoint['y'],
                    'showLabel' => $sweepAngle >= ($doughnut ? 32 : 24),
                ];
            })
            ->all();
    }

    /**
     * @return array{x: float, y: float}
     */
    private function chartPoint(float|int $centerX, float|int $centerY, float|int $radiusX, float|int $radiusY, float $angle): array
    {
        $radians = deg2rad($angle);

        return [
            'x' => $centerX + ($radiusX * cos($radians)),
            'y' => $centerY + ($radiusY * sin($radians)),
        ];
    }
}
