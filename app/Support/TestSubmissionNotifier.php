<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\Groups;
use App\Models\Notifs;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Support\Str;

final class TestSubmissionNotifier
{
    /**
     * Уведомления для сотрудников с правами tests_admin/tests_stats,
     * привязанных к той же группе, что и студент.
     */
    public static function notifyStaffAboutGroupTestSubmission(Employee $student, Test $test, TestAttempt $attempt): void
    {
        $groupId = (int) ($student->group_id ?? 0);
        if ($groupId <= 0) {
            return;
        }

        $groupName = Groups::find($groupId)?->name ?? 'без группы';
        $gradeLabel = TestGrading::labelRu((string) $attempt->grade);
        $title = 'Тест группы сдан';
        $message = sprintf(
            '%s (гр. «%s») завершил тест «%s»: %s/%s балл., %s%%, оц. %s (%s).',
            $student->fio,
            $groupName,
            $test->title,
            (string) $attempt->score,
            (string) $attempt->max_score,
            (string) $attempt->percentage,
            (string) $attempt->grade,
            $gradeLabel
        );
        $message = Str::limit($message, 250);

        $recipientIds = Employee::query()
            ->where('group_id', $groupId)
            ->whereHas('role.pagePermissions', function ($q) {
                $q->whereIn('page_key', ['tests_admin', 'tests_stats']);
            })
            ->where('id', '!=', (int) $student->id)
            ->pluck('id')
            ->unique()
            ->all();

        foreach ($recipientIds as $employeeId) {
            Notifs::create([
                'title' => $title,
                'message' => $message,
                'employee_id' => $employeeId,
            ]);
        }
    }
}
