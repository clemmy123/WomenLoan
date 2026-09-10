<?php

namespace App\Services\Concerns;

use App\Support\ByReportChartPayload;

trait BuildsByReportChartPayload
{
  /**
   * @return array{
   *     financial: array{labels: list<string>, data: list<float>},
   *     loan_type: array{labels: list<string>, data: list<int>},
   *     top_disbursed: array{labels: list<string>, data: list<float>}
   * }
   */
    public function chartPayload(array $filters): array
    {
        return ByReportChartPayload::build($this->allRows($filters), $this->summary($filters));
    }
}
