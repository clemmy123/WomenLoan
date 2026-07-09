<?php

namespace App\Support;

class WorkflowSteps
{
    public const ROLES = [
        1 => 'cdo_ward',
        2 => 'cdo_ministry',
        3 => 'applicant',
        4 => 'cdo_ministry',
        5 => 'assistant_director',
        6 => 'director',
        7 => 'km',
        8 => 'chief',
        9 => 'accountant',
    ];

    public const LABELS = [
        1 => 'Ward',
        2 => 'Ministry',
        3 => 'Applicant',
        4 => 'Ministry',
        5 => 'Ass. Director',
        6 => 'Director',
        7 => 'Permanent Secretary',
        8 => 'Chief',
        9 => 'Accountant',
    ];

    public const ROLE_STEP_MAP = [
        'cdo_ward' => [1],
        'cdo_ministry' => [2, 4],
        'assistant_director' => [5],
        'director' => [6],
        'km' => [7],
        'chief' => [8],
        'accountant' => [9],
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
        return 'S' . $step;
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
