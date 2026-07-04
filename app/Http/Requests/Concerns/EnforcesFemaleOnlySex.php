<?php

namespace App\Http\Requests\Concerns;

trait EnforcesFemaleOnlySex
{
    protected function enforceFemaleOnlySex(array $fields = ['sex']): void
    {
        $merge = [];

        foreach ($fields as $field) {
            if ($this->has($field)) {
                $merge[$field] = 'Female';
            }
        }

        if ($this->has('leader') && is_array($this->input('leader'))) {
            $leader = $this->input('leader');
            $leader['sex'] = 'Female';
            $merge['leader'] = $leader;
        }

        if ($this->has('members') && is_array($this->input('members'))) {
            $members = $this->input('members');

            foreach ($members as $index => $member) {
                if (is_array($member)) {
                    $members[$index]['sex'] = 'Female';
                }
            }

            $merge['members'] = $members;
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }
}
