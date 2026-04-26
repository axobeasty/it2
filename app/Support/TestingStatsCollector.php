<?php

namespace App\Support;

use App\Models\Groups;
use App\Models\TestAttempt;
use Illuminate\Http\Request;

/**
 * Сводная статистика прохождения тестов (как веб /tests/stats).
 */
class TestingStatsCollector
{
    /**
     * @return array{groupId: int, attempts: \Illuminate\Contracts\Pagination\LengthAwarePaginator, statsByGroup: \Illuminate\Support\Collection, filterLabel: string}
     */
    public static function collect(Request $request): array
    {
        $groupId = (int) $request->query('group_id', 0);

        $baseQuery = TestAttempt::query()
            ->when($groupId > 0, fn ($q) => $q->where('group_id', $groupId));

        $attempts = (clone $baseQuery)
            ->with(['test', 'student.group'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $statsByGroupRows = (clone $baseQuery)
            ->leftJoin('groups', 'groups.id', '=', 'test_attempts.group_id')
            ->selectRaw("COALESCE(groups.name, 'Без группы') as group_name")
            ->selectRaw('COUNT(*) as attempts_count')
            ->selectRaw('ROUND(AVG(test_attempts.percentage), 2) as avg_percentage')
            ->selectRaw('ROUND(MIN(test_attempts.percentage), 2) as min_percentage')
            ->selectRaw('ROUND(MAX(test_attempts.percentage), 2) as max_percentage')
            ->groupBy('groups.name')
            ->orderBy('group_name')
            ->get();

        $statsByGroup = $statsByGroupRows->mapWithKeys(fn ($row) => [
            $row->group_name => [
                'count' => (int) $row->attempts_count,
                'avg' => (float) $row->avg_percentage,
                'min' => (float) $row->min_percentage,
                'max' => (float) $row->max_percentage,
            ],
        ]);

        $filterLabel = 'Все группы';
        if ($groupId > 0) {
            $group = Groups::find($groupId);
            $filterLabel = $group ? $group->name : 'Группа #'.$groupId;
        }

        return [
            'groupId' => $groupId,
            'attempts' => $attempts,
            'statsByGroup' => $statsByGroup,
            'filterLabel' => $filterLabel,
        ];
    }
}
