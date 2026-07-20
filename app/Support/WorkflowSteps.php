<?php

namespace App\Support;

class WorkflowSteps
{
    public const ROLES = [
        1 => 'cdo_ward',
        2 => 'cdo_council',
        3 => 'cdo_ministry',
        4 => 'applicant',
        5 => 'cdo_ministry',
        6 => 'assistant_director',
        7 => 'director',
        8 => 'km',
        9 => 'chief',
        10 => 'accountant',
    ];

    public const LABELS = [
        1 => 'Ward',
        2 => 'Council',
        3 => 'Ministry',
        4 => 'Applicant',
        5 => 'Ministry',
        6 => 'Ass. Director',
        7 => 'Director',
        8 => 'Permanent Secretary',
        9 => 'Chief',
        10 => 'Accountant',
    ];

    public const ROLE_STEP_MAP = [
        'cdo_ward' => [1],
        'cdo_council' => [2],
        'cdo_ministry' => [3, 5],
        'assistant_director' => [6],
        'director' => [7],
        'km' => [8],
        'chief' => [9],
        'accountant' => [10],
    ];

    public static function roleForStep(int $step): ?string
    {
        return self::ROLES[$step] ?? null;
    }

    public static function labelForStep(int $step): string
    {
        return self::LABELS[$step] ?? "Step {$step}";
    }

    public static function shortLabelForStep(int $step): string
    {
        return 'S'.$step;
    }

    public static function stepsForRole(string $role): array
    {
        return self::ROLE_STEP_MAP[$role] ?? [];
    }

    public static function pipelineLabels(): array
    {
        $labels = [];
        $shortLabels = [];

        foreach (self::LABELS as $num => $name) {
            $labels[] = "Step {$num}: {$name}";
            $shortLabels[] = self::shortLabelForStep($num);
        }

        return compact('labels', 'shortLabels');
    }
}
